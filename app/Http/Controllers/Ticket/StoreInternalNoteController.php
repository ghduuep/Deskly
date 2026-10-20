<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreInternalNoteRequest;
use App\Http\Resources\MessageResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StoreInternalNoteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        StoreInternalNoteRequest $request,
        Ticket $ticket,
    ): JsonResponse {
        $contact = $request->user()->contact()->firstOrFail();

        $message = $ticket->addMessage(
            $contact,
            $request->validated("content"),
            isInternal: true,
        );

        return new MessageResource($message->load("author"))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
