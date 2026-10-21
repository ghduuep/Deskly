<?php

namespace App\Models;

use App\Events\Message\InternalNoteAdded;
use App\Events\Message\MessageSent;
use App\Events\Ticket\TicketAssigned;
use App\Events\Ticket\TicketClosed;
use App\Events\Ticket\TicketPriorityChanged;
use App\Events\Ticket\TicketReopened;
use App\Events\Ticket\TicketResolved;
use App\Events\Ticket\TicketUnassigned;
use App\Events\Ticket\TicketPended;
use App\Events\Ticket\TicketResumed;
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
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

#[Fillable(["title", "status", "contact_id", "assigned_id", "priority"])]
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
        if ($this->status === TicketStatus::Pending) {
            return;
        }

        if ($this->status !== TicketStatus::Open) {
            throw new DomainException(
                "Only open tickets can be marked as pending.",
            );
        }

        $this->update(["status" => TicketStatus::Pending]);

        TicketPended::dispatch($this->id);
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
        if ($this->status === TicketStatus::Open) {
            return;
        }

        if ($this->status !== TicketStatus::Pending) {
            throw new DomainException("Only pending tickets can be resumed.");
        }

        $this->update(["status" => TicketStatus::Open]);

        TicketResumed::dispatch($this->id);
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
        if ($this->status === TicketStatus::Resolved) {
            return;
        }

        if ($this->status === TicketStatus::Closed) {
            throw new DomainException("Cannot resolve a closed ticket.");
        }
        $this->update(["status" => TicketStatus::Resolved]);

        TicketResolved::dispatch(ticketId: $this->id);
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
        if ($this->status === TicketStatus::Closed) {
            return;
        }

        if ($this->status !== TicketStatus::Resolved) {
            throw new DomainException("Only resolved tickets can be closed.");
        }
        $this->update(["status" => TicketStatus::Closed]);

        TicketClosed::dispatch(ticketId: $this->id);
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
        if ($this->status === TicketStatus::Open) {
            return;
        }

        if (
            !in_array(
                $this->status,
                [TicketStatus::Resolved, TicketStatus::Closed],
                true,
            )
        ) {
            throw new DomainException("This ticket cannot be reopened.");
        }
        $this->update(["status" => TicketStatus::Open]);

        TicketReopened::dispatch(ticketId: $this->id);
    }

    /**
     * Changes ticket priority.
     *
     * Does nothing if the desired priority is the currently priority.private
     */
    public function changePriority(TicketPriority $priority): void
    {
        if ($this->priority === $priority) {
            return;
        }

        $previousPriority = $this->priority;

        $this->update(["priority" => $priority]);

        TicketPriorityChanged::dispatch(
            $this->id,
            $previousPriority,
            $priority,
        );
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
        if ($this->assigned_id === $user->id) {
            return;
        }

        if (!$user->canBeAssignedTickets()) {
            throw new DomainException("This user cannot be assigned tickets.");
        }
        $this->update(["assigned_id" => $user->id]);

        TicketAssigned::dispatch($this->id, $user->id);
    }

    /**
     * Removes the currently assigned agent from the ticket.
     *
     * Does nothing if the ticket is already unassigned.
     */
    public function unassign(): void
    {
        if ($this->assigned_id === null) {
            return;
        }

        $previousAssigneeId = $this->assigned_id;

        $this->update(["assigned_id" => null]);

        TicketUnassigned::dispatch($this->id, $previousAssigneeId);
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
        return DB::transaction(function () use ($author, $content, $direction) {
            if ($this->status === TicketStatus::Closed) {
                throw new DomainException(
                    "Cannot add messages to a closed ticket.",
                );
            }

            if ($this->status === TicketStatus::Resolved) {
                if ($direction === MessageDirection::Outbound) {
                    throw new DomainException(
                        "Reopen the ticket before replying.",
                    );
                }
                $this->reopen();
            }

            if (
                $direction === MessageDirection::Inbound &&
                $this->status === TicketStatus::Pending
            ) {
                $this->resume();
            }

            if (
                $direction === MessageDirection::Outbound &&
                $this->status === TicketStatus::Open
            ) {
                $this->pend();
            }

            $message = $this->createMessage(
                $author,
                $content,
                MessageType::Reply,
                $direction,
            );

            MessageSent::dispatch($message->id, $this->id);

            return $message;
        });
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
        if ($this->status === TicketStatus::Closed) {
            throw new DomainException(
                "Cannot add internal notes to a closed ticket.",
            );
        }

        $message = $this->createMessage(
            $author,
            $content,
            MessageType::Internal,
        );

        InternalNoteAdded::dispatch($message->id, $this->id);

        return $message;
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

        return $this->messages()->create([
            "contact_id" => $author->id,
            "content" => $content,
            "type" => $type,
            "direction" => $direction,
        ]);
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, "taggable");
    }
}
