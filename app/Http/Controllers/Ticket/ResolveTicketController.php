<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Support\Facades\Gate;

class ResolveTicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Ticket $ticket): TicketResource
    {
        Gate::authorize('resolve', $ticket);

        $ticket->resolve();

        return new TicketResource($ticket->refresh());
    }
}
