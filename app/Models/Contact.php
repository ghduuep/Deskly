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

#[Fillable(["user_id", "company_id", "name", "email", "phone"])]
class Contact extends Model
{
    use HasTags, HasFactory;

    protected function casts(): array
    {
        return [
            "email" => EmailAddressCast::class,
            "phone" => PhoneNumberCast::class,
        ];
    }

    public function changeEmail(EmailAddress $email): void
    {
        $this->update(["email" => $email]);
    }

    public function changePhone(PhoneNumber $phone): void
    {
        $this->update(["phone" => $phone]);
    }

    public function assignToCompany(Company $company): void
    {
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
}
