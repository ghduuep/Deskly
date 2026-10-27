<?php

namespace App\Models;

use App\Enum\MessageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(["ticket_id", "user_id", "type", "content"])]
class Draft extends Model
{
    /** @use HasFactory<\Database\Factories\DraftFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            "type" => MessageType::class,
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, "attachable");
    }
}
