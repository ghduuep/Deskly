<?php

use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Ticket\AssignTicketController;
use App\Http\Controllers\Ticket\ChangePriorityTicketController;
use App\Http\Controllers\Ticket\CloseTicketController;
use App\Http\Controllers\Ticket\MessageController;
use App\Http\Controllers\Ticket\ReopenTicketController;
use App\Http\Controllers\Ticket\ResolveTicketController;
use App\Http\Controllers\Ticket\StoreInternalNoteController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', MeController::class);
    Route::apiResource('tickets', TicketController::class)->only([
        'index',
        'store',
        'show',
    ]);

    Route::prefix('tickets/{ticket}')
        ->name('tickets.')
        ->group(function () {
            Route::patch('/assigment', AssignTicketController::class)->name(
                'assign',
            );
            Route::patch(
                '/priority',
                ChangePriorityTicketController::class,
            )->name('priority');
            Route::post('/resolve', ResolveTicketController::class)->name(
                'resolve',
            );
            Route::post('/close', CloseTicketController::class)->name('close');
            Route::post('/reopen', ReopenTicketController::class)->name(
                'reopen',
            );
        });

    Route::get('tickets/{ticket}/messages', [
        MessageController::class,
        'index',
    ])->name('tickets.messages.index');

    Route::post('tickets/{ticket}/messages', [
        MessageController::class,
        'store',
    ])->name('tickets.messages.store');

    Route::post(
        'tickets/{ticket}/notes',
        StoreInternalNoteController::class,
    )->name('tickets.notes.store');
});
