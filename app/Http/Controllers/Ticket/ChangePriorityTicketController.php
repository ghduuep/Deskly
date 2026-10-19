<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\TicketPriority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChangePriorityTicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Ticket $ticket, TicketPriority $priority, User $user): TicketResource
    {
        Gate::authorize("changePriority", $user);

        $ticket->changePriority($priority);

        return new TicketResource($ticket->refresh());
    }
}
