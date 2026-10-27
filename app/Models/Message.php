<?php

namespace App\Models;

use App\Enum\MessageDirection;
use App\Enum\MessageType;
use App\Enum\TicketStatus;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    "content",
    "type",
    "direction",
    "ticket_id",
    "contact_id",
    "channel_id",
])]
class Message extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            "type" => MessageType::class,
            "direction" => MessageDirection::class,
        ];
    }

    public function addAttachment(Attachment $attachment): void
    {
        if (!$this->exists) {
            throw new DomainException(
                "Message must be persisted before attaching files.",
            );
        }

        if ($this->ticket->status === TicketStatus::Closed) {
            throw new DomainException(
                "Cannot add attachments to a closed ticket.",
            );
        }

        $attachment->attachTo($this);
    }

    public function isInternal(): bool
    {
        return $this->type === MessageType::Internal;
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Contact::class, "contact_id");
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, "attachable");
    }
}
