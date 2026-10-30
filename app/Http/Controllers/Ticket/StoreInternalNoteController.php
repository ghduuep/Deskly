<?php

namespace App\Http\Controllers\Ticket;

use App\Actions\Ticket\AddInternalNote;
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
        AddInternalNote $addInternalNote,
    ): JsonResponse {
        $message = $addInternalNote->handle(
            $ticket,
            $request->user()->contact()->firstOrFail(),
            $request->validated('content'),
        );

        return new MessageResource($message->load('author'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
