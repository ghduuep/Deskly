<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;

class AssignTicketController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        AssignTicketRequest $request,
        Ticket $ticket,
    ): TicketResource {
        $data = $request->validated();

        $assignee = User::findOrFail($data['assigned_id']);

        $ticket->assignTo($assignee);

        return new TicketResource(
            $ticket->refresh()->load(['requester', 'assignee']),
        );
    }
}
