<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["user_id", "company_id", "name", "email", "phone"])]
class Contact extends Model
{
    use HasTags, HasFactory;

    public function changeEmail(string $email): void
    {
        $this->update(["email" => $email]);
    }

    public function changePhone(string $phone): void
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
