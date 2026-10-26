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
        $agent = User::query()->findOrFail($user->id);

        if ($this->group_id === null) {
            throw new DomainException("Assign the ticket to a group first.");
        }

        if (!$agent->canBeAssignedTickets()) {
            throw new DomainException("This user cannot be assigned tickets.");
        }

        if (!$this->group->hasUser($agent)) {
            throw new DomainException(
                "The agent must belong to the ticket group.",
            );
        }

        if ($this->assigned_id === $agent->id) {
            return;
        }

        $this->update([
            "assigned_id" => $agent->id,
        ]);

        TicketAssigned::dispatch($this->id, $agent->id);
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

    public function assignToGroup(Group $group): void
    {
        if ($this->group_id === $group->id) {
            return;
        }

        $previousGroupId = $this->group_id;
        $previousAssignedId = $this->assigned_id;

        $shouldUnassign =
            $previousAssignedId !== null && !$group->hasUser($this->assignee);

        $this->update([
            "group_id" => $group->id,
            "assigned_id" => $shouldUnassign ? null : $previousAssignedId,
        ]);

        if ($shouldUnassign) {
            TicketUnassigned::dispatch($this->id, $previousAssignedId);
        }

        TicketGroupChanged::dispatch($this->id, $previousGroupId, $group->id);
    }

    public function addReply(
        Contact $author,
        string $content,
        MessageDirection $direction,
        Channel $channel,
    ): Message {
        if ($this->status === TicketStatus::Closed) {
            throw new DomainException("Cannot reply to a closed ticket.");
        }

        if ($this->status === TicketStatus::Resolved) {
            if ($direction === MessageDirection::Outbound) {
                throw new DomainException("Reopen the ticket before replying.");
            }
        }

        $message = $this->messages()->create([
            "contact_id" => $author->id,
            "channel_id" => $channel->id,
            "content" => $content,
            "type" => MessageType::Reply,
            "direction" => $direction,
        ]);

        if ($direction === MessageDirection::Inbound) {
            match ($this->status) {
                TicketStatus::Resolved => $this->reopen(),
                TicketStatus::Pending => $this->resume(),
                default => null,
            };
        } elseif ($this->status === TicketStatus::Open) {
            $this->pend();
        }

        MessageSent::dispatch($message->id, $this->id);

        return $message;
    }

    public function addInternalNote(Contact $author, string $content): Message
    {
        if ($this->status === TicketStatus::Closed) {
            throw new DomainException(
                "Cannot add internal notes to a closed ticket.",
            );
        }

        $message = $this->messages()->create([
            "contact_id" => $author->id,
            "content" => $content,
            "type" => MessageType::Internal,
            "direction" => null,
        ]);

        InternalNoteAdded::dispatch($message->id, $this->id);

        return $message;
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

    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class);
    }
}
