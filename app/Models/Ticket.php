<?php

namespace App\Models;

use App\Events\Message\InternalNoteAdded;
use App\Events\Message\MessageSent;
use App\Events\Ticket\TicketAssigned;
use App\Events\Ticket\TicketClosed;
use App\Events\Ticket\TicketGroupChanged;
use App\Events\Ticket\TicketPended;
use App\Events\Ticket\TicketPriorityChanged;
use App\Events\Ticket\TicketReopened;
use App\Events\Ticket\TicketResolved;
use App\Events\Ticket\TicketResumed;
use App\Events\Ticket\TicketUnassigned;
use App\HtmlSanitizer;
use App\MessageDirection;
use App\MessageType;
use App\Models\Concerns\HasTags;
use App\TicketPriority;
use App\TicketStatus;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

#[Fillable([
    "title",
    "status",
    "contact_id",
    "assigned_id",
    "group_id",
    "priority",
])]
class Ticket extends Model
{
    use HasFactory, HasTags;

    protected function casts(): array
    {
        return [
            "status" => TicketStatus::class,
            "priority" => TicketPriority::class,
        ];
    }

    /**
     * Marks an open ticket as pending.
     *
     * Does nothing if the ticket is already pending.
     *
     * @throws
     */
    public function pend(): void
    {
        $ticket = DB::transaction(function () {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->status === TicketStatus::Pending) {
                return $ticket;
            }

            if ($ticket->status !== TicketStatus::Open) {
                throw new DomainException(
                    "Only open tickets can be marked as pending.",
                );
            }

            $ticket->update(["status" => TicketStatus::Pending]);

            TicketPended::dispatch($ticket->id);

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Resumes a pending ticket.
     *
     * Does nothing if the ticket is already open.
     *
     * @throws DomainException If the ticket cannot be resumed.
     */
    public function resume(): void
    {
        $ticket = DB::transaction(function () {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);
            if ($ticket->status === TicketStatus::Open) {
                return $ticket;
            }

            if ($ticket->status !== TicketStatus::Pending) {
                throw new DomainException(
                    "Only pending tickets can be resumed.",
                );
            }

            $ticket->update(["status" => TicketStatus::Open]);

            TicketResumed::dispatch($ticket->id);

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Marks the ticket as resolved.
     *
     * Does nothing if the ticket is already resolved.
     *
     * @throws DomainException If the ticket is closed.
     */
    public function resolve(): void
    {
        $ticket = DB::transaction(function () {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->status === TicketStatus::Resolved) {
                return $ticket;
            }

            if ($ticket->status === TicketStatus::Closed) {
                throw new DomainException("Cannot resolve a closed ticket.");
            }
            $ticket->update(["status" => TicketStatus::Resolved]);

            TicketResolved::dispatch(ticketId: $ticket->id);

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Closes a resolved ticket.
     *
     * Does nothing if the ticket is already closed.
     *
     * @throws DomainException If the ticket is not resolved.
     */
    public function close(): void
    {
        $ticket = DB::transaction(function () {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->status === TicketStatus::Closed) {
                return $ticket;
            }

            if ($ticket->status !== TicketStatus::Resolved) {
                throw new DomainException(
                    "Only resolved tickets can be closed.",
                );
            }
            $ticket->update(["status" => TicketStatus::Closed]);

            TicketClosed::dispatch(ticketId: $ticket->id);

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Reopens a resolved or closed ticket.
     *
     * Does nothing if the ticket is already open.
     *
     * @throws DomainException If the ticket cannot be reopened.
     */
    public function reopen(): void
    {
        $ticket = DB::transaction(function () {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->status === TicketStatus::Open) {
                return $ticket;
            }

            if (
                !in_array(
                    $ticket->status,
                    [TicketStatus::Resolved, TicketStatus::Closed],
                    true,
                )
            ) {
                throw new DomainException("This ticket cannot be reopened.");
            }
            $ticket->update(["status" => TicketStatus::Open]);

            TicketReopened::dispatch(ticketId: $ticket->id);

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Changes ticket priority.
     *
     * Does nothing if the desired priority is the currently priority.private
     */
    public function changePriority(TicketPriority $priority): void
    {
        $ticket = DB::transaction(function () use ($priority) {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->priority === $priority) {
                return $ticket;
            }

            $previousPriority = $ticket->priority;

            $ticket->update(["priority" => $priority]);

            TicketPriorityChanged::dispatch(
                $ticket->id,
                $previousPriority,
                $priority,
            );

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Assigns the ticket to an agent or administrator.
     *
     * Does nothing if the user is already assigned.
     *
     * @throws DomainException If the user cannot be assigned tickets.
     */
    public function assignTo(User $user): void
    {
        $ticket = DB::transaction(function () use ($user) {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->group_id === null) {
                throw new DomainException(
                    "Assign the ticket to a group first.",
                );
            }

            if (!$user->canBeAssignedTickets()) {
                throw new DomainException(
                    "This user cannot be assigned tickets.",
                );
            }

            if (!$ticket->group->hasUser($user)) {
                throw new DomainException(
                    "The agent must belong to the ticket group.",
                );
            }

            if ($ticket->assigned_id === $user->id) {
                return $ticket;
            }

            $ticket->update([
                "assigned_id" => $user->id,
            ]);

            TicketAssigned::dispatch($ticket->id, $user->id);

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Removes the currently assigned agent from the ticket.
     *
     * Does nothing if the ticket is already unassigned.
     */
    public function unassign(): void
    {
        $ticket = DB::transaction(function () {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->assigned_id === null) {
                return $ticket;
            }

            $previousAssigneeId = $ticket->assigned_id;

            $ticket->update(["assigned_id" => null]);

            TicketUnassigned::dispatch($ticket->id, $previousAssigneeId);

            return $ticket;
        }, 3);

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    /**
     * Adds a public message to the ticket.
     *
     * Reopens if the ticket is resolved.
     *
     * @throws DomainException If the ticket is closed.
     */
    public function addMessage(
        Contact $author,
        string $content,
        MessageDirection $direction,
    ): Message {
        $message = DB::transaction(function () use (
            $author,
            $content,
            $direction,
        ) {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->status === TicketStatus::Closed) {
                throw new DomainException(
                    "Cannot add messages to a closed ticket.",
                );
            }

            if ($ticket->status === TicketStatus::Resolved) {
                if ($direction === MessageDirection::Outbound) {
                    throw new DomainException(
                        "Reopen the ticket before replying.",
                    );
                }
                $ticket->reopen();
            }

            if (
                $direction === MessageDirection::Inbound &&
                $ticket->status === TicketStatus::Pending
            ) {
                $ticket->resume();
            }

            if (
                $direction === MessageDirection::Outbound &&
                $ticket->status === TicketStatus::Open
            ) {
                $ticket->pend();
            }

            $message = $ticket->createMessage(
                $author,
                $content,
                MessageType::Reply,
                $direction,
            );

            MessageSent::dispatch($message->id, $ticket->id);

            return $message;
        }, 3);

        $this->refresh();

        return $message;
    }

    /**
     * Adds an internal note to the ticket.
     *
     * Internal notes do not reopen resolved tickets.
     *
     * @throws DomainException If the ticket is closed.
     */
    public function addInternalNote(Contact $author, string $content): Message
    {
        return DB::transaction(function () use ($author, $content) {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->status === TicketStatus::Closed) {
                throw new DomainException(
                    "Cannot add internal notes to a closed ticket.",
                );
            }

            $message = $ticket->createMessage(
                $author,
                $content,
                MessageType::Internal,
            );

            InternalNoteAdded::dispatch($message->id, $ticket->id);

            return $message;
        }, 3);
    }

    private function createMessage(
        Contact $author,
        string $content,
        MessageType $type,
        ?MessageDirection $direction = null,
    ): Message {
        if ($type === MessageType::Reply && $direction === null) {
            throw new InvalidArgumentException(
                "Public replies required a direction.",
            );
        }

        if ($type === MessageType::Internal && $direction !== null) {
            throw new InvalidArgumentException(
                "Internal notes cannot have a direction.",
            );
        }

        $content = app(HtmlSanitizer::class)->sanitize($content);

        return $this->messages()->create([
            "contact_id" => $author->id,
            "content" => $content,
            "type" => $type,
            "direction" => $direction,
        ]);
    }

    public function assignToGroup(Group $group): void
    {
        $ticket = DB::transaction(function () use ($group) {
            $ticket = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($ticket->group_id === $group->id) {
                return $ticket;
            }

            $previousGroupId = $ticket->group_id;
            $previousAssignedId = $ticket->assigned_id;

            $shouldUnassign =
                $previousAssignedId !== null &&
                !$group->hasUser($ticket->assignee);

            $ticket->update([
                "group_id" => $group->id,
                "assigned_id" => $shouldUnassign ? null : $previousAssignedId,
            ]);

            if ($shouldUnassign) {
                TicketUnassigned::dispatch($ticket->id, $previousAssignedId);
            }

            TicketGroupChanged::dispatch(
                $ticket->id,
                $previousGroupId,
                $group->id,
            );

            return $ticket;
        });

        $this->setRawAttributes($ticket->getAttributes(), true);
        $this->unsetRelations();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Contact::class, "contact_id");
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, "assigned_id");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
