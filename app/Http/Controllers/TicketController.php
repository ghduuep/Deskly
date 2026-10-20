<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\TicketPriority;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = $request->user();

        $tickets = Ticket::query()
            ->when(
                $user->role === UserRole::Customer,
                fn ($query) => $query->where(
                    'contact_id',
                    $user->contact()->firstOrFail()->id,
                ),
            )
            ->with(['requester', 'assignee'])
            ->latest()
            ->paginate(15);

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();

        if ($user->role === UserRole::Customer) {
            $data['contact_id'] = $user->contact()->firstOrFail()->id;
        }

        $ticket = Ticket::create([
            ...$data,
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Normal,
        ]);

        return new TicketResource($ticket->load(['requester', 'assignee']))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return new TicketResource(
            $ticket->load(['requester', 'assignee', 'company', 'tags']),
        );
    }
}
