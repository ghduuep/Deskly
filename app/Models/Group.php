<?php

namespace App\Models;

use Database\Factories\GroupFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(["name", "description"])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    public function addUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $group = self::query()->lockForUpdate()->findOrFail($this->id);
            $agent = User::query()->lockForUpdate()->findOrFail($user->id);

            if (!$agent->canBeAssignedTickets()) {
                throw new DomainException(
                    "Only agents and administrators can join groups.",
                );
            }

            $group->users()->syncWithoutDetaching([$agent->id]);
        });
    }

    public function removeUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $group = self::query()->lockForUpdate()->findOrFail($this->id);

            $member = $group->users()->whereKey($user->id)->exists();

            if (!$member) {
                return;
            }

            $tickets = $group
                ->tickets()
                ->where("assigned_id", $user->id)
                ->lockForUpdate()
                ->get();

            foreach ($tickets as $ticket) {
                $ticket->unassign();
            }

            $group->users()->detach($user->id);
        });
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
