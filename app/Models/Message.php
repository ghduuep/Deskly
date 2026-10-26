<?php

namespace App\Models;

use App\MessageDirection;
use App\MessageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
