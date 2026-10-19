<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReopenTicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Ticket $ticket): TicketResource
    {
        Gate::authorize("reopen", $ticket);

        $ticket->reopen();

        return new TicketResource($ticket->refresh());
    }
}
