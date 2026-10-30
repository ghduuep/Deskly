<?php

namespace App\Http\Controllers\Ticket;

use App\Actions\Ticket\AddTicketMessage;
use App\Enum\ChannelType;
use App\Enum\MessageDirection;
use App\Enum\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Channel;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function index(
        Request $request,
        Ticket $ticket,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ticket);

        $query = $ticket->messages()->with('author')->orderBy('id');

        if (! $request->user()->canBeAssignedTickets()) {
            $query->where('type', MessageType::Reply);
        }

        return MessageResource::collection($query->paginate(20));
    }

    public function store(
        StoreMessageRequest $request,
        Ticket $ticket,
        AddTicketMessage $addTicketMessage,
    ): JsonResponse {
        $user = $request->user();

        $channel = Channel::query()->firstOrCreate(
            ['type' => ChannelType::Web],
            ['name' => 'Web'],
        );

        $message = $addTicketMessage->handle(
            $ticket,
            $user->contact()->firstOrFail(),
            $request->validated('content'),
            $user->canBeAssignedTickets()
                ? MessageDirection::Outbound
                : MessageDirection::Inbound,
            $channel,
        );

        return new MessageResource($message->load('author'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
