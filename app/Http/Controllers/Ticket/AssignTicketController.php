<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignTicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Ticket $ticket, User $user): TicketResource
    {
        Gate::authorize("assign", $ticket);

        $ticket->assignTo($user);

        return new TicketResource($ticket->refresh());
    }
}
