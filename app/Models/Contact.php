<?php

namespace App\Models;

use App\Enum\ContactIdentityType;
use App\Models\Concerns\HasTags;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a person who can participate in support interactions.
 *
 * Contacts may originate from external communication channels
 * and do not required an associated authenticated user.
 */
#[Fillable(["user_id", "company_id", "name"])]
class Contact extends Model
{
    use HasFactory, HasTags;

    public function addEmailIdentity(EmailAddress $email): ContactIdentity
    {
        return $this->identities()->create([
            "type" => ContactIdentityType::Email,
            "identifier" => (string) $email,
        ]);
    }

    public function addWhatsappIdentity(PhoneNumber $phone): ContactIdentity
    {
        return $this->identities()->create([
            "type" => ContactIdentityType::Whatsapp,
            "identifier" => (string) $phone,
        ]);
    }

    public function assignToCompany(Company $company): void
    {
        if ($this->company_id === $company->id) {
            return;
        }
        $this->update(["company_id" => $company->id]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(ContactIdentity::class);
    }
}
