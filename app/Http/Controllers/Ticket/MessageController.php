<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreMessageRequest;
use App\Http\Resources\MessageResource;
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
        Gate::authorize("view", $ticket);

        $query = $ticket->messages()->with("author")->orderBy("id");

        if (!$request->user()->canBeAssignedTickets()) {
            $query->where("is_internal", false);
        }

        return MessageResource::collection($query->paginate(20));
    }

    public function store(
        StoreMessageRequest $request,
        Ticket $ticket,
    ): JsonResponse {
        $contact = $request->user()->contact()->firstOrFail();

        $message = $ticket->addMessage(
            $contact,
            $request->validated("content"),
        );

        return new MessageResource($message->load("author"))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
