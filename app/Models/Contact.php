<?php

namespace App\Models;

use App\Casts\EmailAddressCast;
use App\Casts\PhoneNumberCast;
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
#[Fillable(['user_id', 'company_id', 'name', 'email', 'phone'])]
class Contact extends Model
{
    use HasFactory, HasTags;

    protected function casts(): array
    {
        return [
            'email' => EmailAddressCast::class,
            'phone' => PhoneNumberCast::class,
        ];
    }

    public function changeEmail(EmailAddress $email): void
    {
        if ($this->email?->equals($email) === true) {
            return;
        }

        $this->update(['email' => $email]);
    }

    public function changePhone(PhoneNumber $phone): void
    {
        if ($this->phone?->equals($phone) === true) {
            return;
        }
        $this->update(['phone' => $phone]);
    }

    public function assignToCompany(Company $company): void
    {
        if ($this->company_id === $company->id) {
            return;
        }
        $this->update(['company_id' => $company->id]);
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
}
