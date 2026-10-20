<?php

use App\Models\Contact;
use App\Models\Ticket;
use App\Models\User;
use App\TicketPriority;
use App\TicketStatus;
use App\UserRole;

it('can resolve an open ticket', function () {
    $ticket = Ticket::factory()->create();

    $ticket->resolve();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('can close a resolved ticket', function () {
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
    ]);

    $ticket->close();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);
});

it('can reopen an closed ticket', function () {
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
    ]);

    $ticket->close();

    $ticket->reopen();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
});

it('can change ticket priority', function () {
    $ticket = Ticket::factory()->create();

    $ticket->changePriority(TicketPriority::Urgent);

    expect($ticket->fresh()->priority)->toBe(TicketPriority::Urgent);
});

it('can assign a ticket to an agent', function () {
    $ticket = Ticket::factory()->create(); // By default, the assigned_id is NULL
    $user = User::factory()->create([
        'role' => UserRole::Agent,
    ]);

    $ticket->assignTo($user);

    expect($ticket->fresh()->assigned_id)->toBe($user->id);
});

it('can add a message to an existing ticket', function () {
    $ticket = Ticket::factory()->create();
    $contact = Contact::factory()->create();

    $message = $ticket->addMessage($contact, 'Hello I need help', false);

    expect($ticket->fresh()->messages)->toHaveCount(1);
    expect($message->author->is($contact))->toBeTrue();
    expect($message->ticket->is($ticket))->toBeTrue();
});

it('should not resolve a closed ticket', function () {
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Closed,
    ]);

    expect(fn () => $ticket->resolve())->toThrow(DomainException::class);
});

it('should not close a non resolved ticket', function (TicketStatus $status) {
    $ticket = Ticket::factory()->create([
        'status' => $status,
    ]);

    expect(fn () => $ticket->close())->toThrow(DomainException::class);
})->with([
    'Open tickets cannot be closed' => [TicketStatus::Open],
    'Pending tickets cannot be closed' => [TicketStatus::Pending],
]);

it('should not reopen a pending ticket', function () {
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Pending,
    ]);

    expect(fn () => $ticket->reopen())->toThrow(DomainException::class);
});

it('should not assign a ticket to a customer', function () {
    $ticket = Ticket::factory()->create();
    $user = User::factory()->create();

    expect(fn () => $ticket->assignTo($user))->toThrow(DomainException::class);
});
