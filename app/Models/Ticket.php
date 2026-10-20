<?php

namespace App\Models;

use App\Events\MessageSent;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketPriorityChanged;
use App\Events\TicketReopened;
use App\Events\TicketResolved;
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

#[Fillable(["title", "status", "contact_id", "assigned_id", "priority"])]
class Ticket extends Model
{
    use HasTags, HasFactory;

    protected function casts(): array
    {
        return [
            "status" => TicketStatus::class,
            "priority" => TicketPriority::class,
        ];
    }
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

    public function addMessage(
        Contact $author,
        string $content,
        bool $isInternal = false,
    ): Message {
        $message = $this->messages()->create([
            "contact_id" => $author->id,
            "content" => $content,
            "is_internal" => $isInternal,
        ]);

        MessageSent::dispatch($message->id, $this->id);

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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, "taggable");
    }
}
