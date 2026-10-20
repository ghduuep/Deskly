<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['content', 'is_internal', 'ticket_id', 'contact_id'])]
class Message extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
