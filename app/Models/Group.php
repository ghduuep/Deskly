<?php

namespace App\Models;

use App\Exceptions\Domain\InvalidGroupMemberException;
use Database\Factories\GroupFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["name", "description"])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    public function addUser(User $user): void
    {
        if (!$user->canBeAssignedTickets()) {
            throw new InvalidGroupMemberException(
                "Only agents and administrators can join groups.",
            );
        }

        $this->users()->syncWithoutDetaching([$user->id]);
    }

    public function removeUser(User $user): void
    {
        $member = $this->users()->whereKey($user->id)->exists();

        if (!$member) {
            return;
        }

        $tickets = $this->tickets()->where("assigned_id", $user->id)->get();

        foreach ($tickets as $ticket) {
            $ticket->unassign();
        }

        $this->users()->detach($user->id);
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->whereKey($user->id)->exists();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
