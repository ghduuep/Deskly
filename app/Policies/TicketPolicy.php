<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): Response
    {
        if (
            $ticket->requester?->is($user->contact()) ||
            $user->canBeAssignedTickets()
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->canBeAssignedTickets();
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        return $user->canBeAssignedTickets();
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->canBeAssignedTickets();
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->canBeAssignedTickets();
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->canBeAssignedTickets();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->canBeAssignedTickets();
    }

    public function addMessage(User $user, Ticket $ticket): bool
    {
        return $ticket->requester?->is($user->contact) ||
            $user->canBeAssignedTickets();
    }

    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->canBeAssignedTickets();
    }
}
