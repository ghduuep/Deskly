<?php

use App\Events\Message\InternalNoteAdded;
use App\Events\Message\MessageSent;
use App\Events\TicketAssigned as AppTicketAssigned;
use App\Events\Ticket\TicketAssigned;
use App\Events\Ticket\TicketClosed;
use App\Events\Ticket\TicketPriorityChanged;
use App\Events\Ticket\TicketReopened;
use App\Events\Ticket\TicketResolved;
use App\Events\Ticket\TicketUnassigned;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Ticket;
use App\Models\User;
use App\TicketPriority;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Support\Facades\Event;

it("can resolve an open ticket", function () {
    $ticket = Ticket::factory()->create();

    $ticket->resolve();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it("can close a resolved ticket", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Resolved,
    ]);

    $ticket->close();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);
});

it("can reopen an closed ticket", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Resolved,
    ]);

    $ticket->close();

    $ticket->reopen();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
});

it("can change ticket priority", function () {
    $ticket = Ticket::factory()->create();

    $ticket->changePriority(TicketPriority::Urgent);

    expect($ticket->fresh()->priority)->toBe(TicketPriority::Urgent);
});

it("can assign a ticket to an agent", function () {
    $ticket = Ticket::factory()->create(); // By default, the assigned_id is NULL
    $user = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $ticket->assignTo($user);

    expect($ticket->fresh()->assigned_id)->toBe($user->id);
});

it("can unassign an agent from a ticket", function () {
    $ticket = Ticket::factory()->create(); // By default, the assigned_id is NULL
    $user = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $ticket->assignTo($user);
    $ticket->unassign();

    expect($ticket->fresh()->assigned_id)->toBeNull();
});

it("can add a message to an existing ticket", function () {
    $ticket = Ticket::factory()->create();
    $contact = Contact::factory()->create();

    $message = $ticket->addMessage($contact, "Hello I need help");

    expect($ticket->fresh()->messages)->toHaveCount(1);
    expect($message->author->is($contact))->toBeTrue();
    expect($message->ticket->is($ticket))->toBeTrue();
});

it("can add an internal note to an existing ticket", function () {
    $ticket = Ticket::factory()->create();
    $contact = Contact::factory()->create();

    $message = $ticket->addInternalNote($contact, "Hello I need help");

    expect($ticket->fresh()->messages)->toHaveCount(1);
    expect($message->author->is($contact))->toBeTrue();
    expect($message->ticket->is($ticket))->toBeTrue();
});

it("should not resolve a closed ticket", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Closed,
    ]);

    expect(fn() => $ticket->resolve())->toThrow(DomainException::class);
});

it("should not close a non resolved ticket", function (TicketStatus $status) {
    $ticket = Ticket::factory()->create([
        "status" => $status,
    ]);

    expect(fn() => $ticket->close())->toThrow(DomainException::class);
})->with([
    "Open tickets cannot be closed" => [TicketStatus::Open],
    "Pending tickets cannot be closed" => [TicketStatus::Pending],
]);

it("should not reopen a pending ticket", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Pending,
    ]);

    expect(fn() => $ticket->reopen())->toThrow(DomainException::class);
});

it("should not assign a ticket to a customer", function () {
    $ticket = Ticket::factory()->create();
    $user = User::factory()->create();

    expect(fn() => $ticket->assignTo($user))->toThrow(DomainException::class);
});

it("dispatches an event when a ticket is assigned", function () {
    $ticket = Ticket::factory()->create();

    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    Event::fake([TicketAssigned::class]);

    $ticket->assignTo($agent);

    expect($ticket->fresh()->assigned_id)->toBe($agent->id);

    Event::assertDispatched(
        TicketAssigned::class,
        fn(TicketAssigned $event) => $event->ticketId === $ticket->id &&
            $event->agentId === $agent->id,
    );

    Event::assertDispatchedOnce(TicketAssigned::class);
});

it("dispatches and event when a ticket is unassigned", function () {
    $ticket = Ticket::factory()->create();

    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    Event::fake([TicketUnassigned::class]);

    $ticket->assignTo($agent);
    $ticket->unassign();

    expect($ticket->fresh()->assigned_id)->toBeNull();

    Event::assertDispatched(
        TicketUnassigned::class,
        fn(TicketUnassigned $event) => $event->ticketId === $ticket->id &&
            $event->previousAssigneeId === $agent->id,
    );

    Event::assertDispatchedOnce(TicketUnassigned::class);
});

it("dispatches an event when the ticket priority changes", function () {
    $ticket = Ticket::factory()->create();

    $previousPriority = $ticket->priority;

    Event::fake([TicketPriorityChanged::class]);

    $ticket->changePriority(TicketPriority::Urgent);

    expect($ticket->fresh()->priority)->toBe(TicketPriority::Urgent);

    Event::assertDispatched(
        TicketPriorityChanged::class,
        fn(TicketPriorityChanged $event) => $event->ticketId === $ticket->id &&
            $previousPriority === $event->previousPriority &&
            $ticket->priority === $event->newPriority,
    );

    Event::assertDispatchedOnce(TicketPriorityChanged::class);
});

it("dispatches an event when a ticket is resolved", function () {
    $ticket = Ticket::factory()->create();

    Event::fake([TicketResolved::class]);

    $ticket->resolve();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);

    Event::assertDispatched(
        TicketResolved::class,
        fn(TicketResolved $event) => $ticket->id === $event->ticketId,
    );

    Event::assertDispatchedOnce(TicketResolved::class);
});

it("dispatches an event when a ticket is closed", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Resolved,
    ]);

    Event::fake([TicketClosed::class]);

    $ticket->close();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);

    Event::assertDispatched(
        TicketClosed::class,
        fn(TicketClosed $event) => $ticket->id === $event->ticketId,
    );

    Event::assertDispatchedOnce(TicketClosed::class);
});

it("dispatches an event when a ticket is reopened", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Resolved,
    ]);

    Event::fake([TicketReopened::class]);

    $ticket->reopen();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);

    Event::assertDispatched(
        TicketReopened::class,
        fn(TicketReopened $event) => $ticket->id === $event->ticketId,
    );

    Event::assertDispatchedOnce(TicketReopened::class);
});

it("dispatches an event when a message is added", function () {
    $ticket = Ticket::factory()->create();
    $contact = Contact::factory()->create();

    Event::fake([MessageSent::class]);

    $message = $ticket->addMessage($contact, "I need help");

    expect($ticket->fresh()->messages)->toHaveCount(1);
    expect($message->author->is($contact))->toBeTrue();
    expect($message->ticket->is($ticket))->toBeTrue();

    Event::assertDispatched(
        MessageSent::class,
        fn(MessageSent $event) => $message->id === $event->messageId &&
            $ticket->id === $event->ticketId,
    );

    Event::assertDispatchedOnce(MessageSent::class);
});

it("dispatches an event when an internal note is added", function () {
    $ticket = Ticket::factory()->create();
    $contact = Contact::factory()->create();

    Event::fake([InternalNoteAdded::class]);

    $message = $ticket->addInternalNote($contact, "I need help");

    expect($ticket->fresh()->messages)->toHaveCount(1);
    expect($message->author->is($contact))->toBeTrue();
    expect($message->ticket->is($ticket))->toBeTrue();

    Event::assertDispatched(
        InternalNoteAdded::class,
        fn(InternalNoteAdded $event) => $message->id === $event->messageId &&
            $ticket->id === $event->ticketId,
    );

    Event::assertDispatchedOnce(InternalNoteAdded::class);
});

it("does not dispatch an event when adding the same priority", function () {
    $ticket = Ticket::factory()->create([
        "priority" => TicketPriority::Urgent,
    ]);

    $previousPriority = $ticket->priority;

    Event::fake([TicketPriorityChanged::class]);

    $ticket->changePriority(TicketPriority::Urgent);

    Event::assertNotDispatched(TicketPriorityChanged::class);
});

it(
    "does not dispatch a transition event when the status is unchanged",
    function (TicketStatus $status, string $method, string $event) {
        Event::fake();

        $ticket = Ticket::factory()->create([
            "status" => $status,
        ]);

        $ticket->{$method}();

        expect($ticket->fresh()->status)->toBe($status);

        Event::assertNotDispatched($event);
    },
)->with([
    "already resolved" => [
        TicketStatus::Resolved,
        "resolve",
        TicketResolved::class,
    ],
    "already closed" => [TicketStatus::Closed, "close", TicketClosed::class],
    "already open" => [TicketStatus::Open, "reopen", TicketReopened::class],
]);

it("does not dispatch an event when assigning the same agent", function () {
    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $ticket = Ticket::factory()->create([
        "assigned_id" => $agent->id,
    ]);

    Event::fake([TicketAssigned::class]);

    $ticket->assignTo($agent);

    Event::assertNotDispatched(TicketAssigned::class);
});

it("does not dispatch an event for an invalid assignment", function () {
    $ticket = Ticket::factory()->create();

    $customer = User::factory()->create();

    Event::fake([TicketAssigned::class]);

    expect(fn() => $ticket->assignTo($customer))->toThrow(
        DomainException::class,
    );

    expect($ticket->fresh()->assigned_id)->toBeNull();
});
