<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(["name", "email", "password", "role"])]
#[Hidden(["password", "remember_token"])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "role" => UserRole::class,
        ];
    }

    public function changeRole(UserRole $role): void
    {
        if ($this->role === $role) {
            return;
        }

        $this->update(["role" => $role]);
    }

    public function canBeAssignedTickets(): bool
    {
        return $this->role === UserRole::Agent ||
            $this->role === UserRole::Admin;
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, "assigned_id");
    }

    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, "author_id");
    }
}
