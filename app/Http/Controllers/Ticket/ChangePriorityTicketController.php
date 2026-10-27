<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\ChangeTicketPriorityRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Enum\TicketPriority;

class ChangePriorityTicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ChangeTicketPriorityRequest $request,
        Ticket $ticket,
    ): TicketResource {
        $data = $request->validated();

        $ticket->changePriority(TicketPriority::from($data['priority']));

        return new TicketResource($ticket->refresh());
    }
}
