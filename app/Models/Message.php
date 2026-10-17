<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(["content", "is_internal"])]
class Message extends Model
{
    protected function casts(): array
    {
        return [
            "is_internal" => "boolean",
        ];
    }
    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
