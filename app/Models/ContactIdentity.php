<?php

namespace App\Models;

use App\ContactIdentityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(["contact_id", "type", "identifier"])]
class ContactIdentity extends Model
{
    /** @use HasFactory<\Database\Factories\ContactIdentityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            "type" => ContactIdentityType::class,
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
