<?php

namespace App\Models;

use App\Casts\DomainCast;
use App\ValueObjects\Domain;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["name", "domain"])]
class Company extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            "domain" => DomainCast::class,
        ];
    }

    public function changeName(string $name): void
    {
        if ($this->name === $name) {
            return;
        }
        $this->update(["name" => $name]);
    }

    public function changeDomain(Domain $domain): void
    {
        if ($this->domain->equals($domain)) {
            return;
        }
        $this->update(["domain" => $domain]);
    }

    public function addContact(Contact $contact): void
    {
        $contact->company()->associate($this);
        $contact->save();
    }

    public function removeContact(Contact $contact): void
    {
        $contact->company()->dissociate();
        $contact->save();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
