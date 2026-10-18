<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TicketController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize("viewAny", Ticket::class);

        $tickets = Ticket::query()->with(["requester", "assignee"])->latest()->paginate(15);

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): TicketResource
    {
        $ticket = Ticket::create([
            ...$request->validated(),
            "status" => TicketStatus::Open,
        ]);

        return (new TicketResource($ticket))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize("view", $ticket);

        return new TicketResource($ticket->load([
            "requester",
            "assignee",
            "company",
            "tags",
        ]));
    }
}
