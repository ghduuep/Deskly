<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    "disk",
    "path",
    "original_name",
    "mime_type",
    "size",
    "uploaded_by_id",
])]
class Attachment extends Model
{
    /** @use HasFactory<\Database\Factories\AttachmentFactory> */
    use HasFactory;

    public function attachTo(Model $owner): void
    {
        if ($this->attachable_type !== null) {
            throw new DomainException(
                "Attachment already belongs to an entity.",
            );
        }

        if (!$owner->exists) {
            throw new DomainException("Attachment owner must be persisted.");
        }

        if (
            !in_array(
                $owner::class,
                [Message::class, Draft::class, KnowledgeArticle::class],
                true,
            )
        ) {
            throw new DomainException("Unsupported attachment owner.");
        }

        $this->attachable()->associate($owner);
        $this->save();
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, "uploaded_by_id");
    }
}
