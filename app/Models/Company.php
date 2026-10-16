<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["name", "domain"])]
class Company extends Model
{
    use HasFactory;

    public function changeName(string $name): void
    {
        $this->update(["name" => $name]);
    }

    public function changeDomain(string $domain): void
    {
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
