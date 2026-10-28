<?php

use App\Actions\Ticket\AddInternalNote;
use App\Actions\Ticket\AddTicketMessage;
use App\Enum\MessageDirection;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Enum\UserRole;
use App\Events\Message\InternalNoteAdded;
use App\Events\Message\MessageSent;
use App\Events\Ticket\TicketAssigned;
use App\Events\Ticket\TicketClosed;
use App\Events\Ticket\TicketPriorityChanged;
use App\Events\Ticket\TicketReopened;
use App\Events\Ticket\TicketResolved;
use App\Events\Ticket\TicketUnassigned;
use App\Exceptions\Domain\InvalidMessageException;
use App\Exceptions\Domain\InvalidTicketAssignmentException;
use App\Exceptions\Domain\InvalidTicketStateException;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it("can resolve an open ticket", function () {
    $ticket = Ticket::factory()->create();

    $ticket->resolve();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it("can pend an open ticket", function () {
    $ticket = Ticket::factory()->create();

    $ticket->pend();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Pending);
});

it("can resume a pending ticket", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Pending,
    ]);

    $ticket->resume();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
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
    $group = Group::factory()->create();
    $group->addUser($user);
    $ticket->assignToGroup($group);

    $ticket->assignTo($user);

    expect($ticket->fresh()->assigned_id)->toBe($user->id);
});

it("can unassign an agent from a ticket", function () {
    $ticket = Ticket::factory()->create(); // By default, the assigned_id is NULL
    $user = User::factory()->create([
        "role" => UserRole::Agent,
    ]);
    $group = Group::factory()->create();
    $group->addUser($user);
    $ticket->assignToGroup($group);

    $ticket->assignTo($user);
    $ticket->unassign();

    expect($ticket->fresh()->assigned_id)->toBeNull();
});

it("can add a message to an existing ticket", function () {
    $ticket = Ticket::factory()->create();
    $contact = $ticket->requester;
    $channel = Channel::factory()->create();

    $message = app(AddTicketMessage::class)->handle(
        $ticket,
        $contact,
        "Need help!",
        MessageDirection::Inbound,
        $channel,
    );

    expect($ticket->fresh()->messages)->toHaveCount(1);
    expect($message->author->is($contact))->toBeTrue();
    expect($message->ticket->is($ticket))->toBeTrue();
});

it("can add an internal note to an existing ticket", function () {
    $ticket = Ticket::factory()->create();
    $agent = User::factory()->create(["role" => UserRole::Agent]);
    $contact = Contact::factory()->create(["user_id" => $agent->id]);
    $group = Group::factory()->create();
    $group->addUser($agent);
    $ticket->assignToGroup($group);

    $message = app(AddInternalNote::class)->handle(
        $ticket,
        $contact,
        "Need to check the customer's bill",
    );

    expect($ticket->fresh()->messages)->toHaveCount(1);
    expect($message->author->is($contact))->toBeTrue();
    expect($message->ticket->is($ticket))->toBeTrue();
});

it("rejects inbound replies from other contacts", function () {
    $ticket = Ticket::factory()->create();

    $author = Contact::factory()->create();

    $channel = Channel::factory()->create();

    expect(
        fn() => $ticket->addReply(
            $author,
            "Hello!",
            MessageDirection::Inbound,
            $channel,
        ),
    )->toThrow(InvalidMessageException::class);

    expect($ticket->messages()->count())->toBe(0);
});

it("rejects outbound replies from customers", function () {
    $ticket = Ticket::factory()->create();

    $customer = User::factory()->create([
        "role" => UserRole::Customer,
    ]);

    $author = Contact::factory()->create([
        "user_id" => $customer->id,
    ]);

    $channel = Channel::factory()->create();

    expect(
        fn() => $ticket->addReply(
            $author,
            "Hello!",
            MessageDirection::Outbound,
            $channel,
        ),
    )->toThrow(InvalidMessageException::class);

    expect($ticket->messages()->count())->toBe(0);
});

it("allows an agent in the ticket group to add an internal note", function () {
    Event::fake();

    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $contact = Contact::factory()->create([
        "user_id" => $agent->id,
    ]);

    $group = Group::factory()->create();
    $group->addUser($agent);

    $ticket = Ticket::factory()->create([
        "group_id" => $group->id,
    ]);

    $message = $ticket->addInternalNote($contact, "Internal note");

    expect($message->isInternal())
        ->toBeTrue()
        ->and($ticket->messages()->count())
        ->toBe(1);
});

it("rejects internal notes from customers", function () {
    $customer = User::factory()->create([
        "role" => UserRole::Customer,
    ]);

    $contact = Contact::factory()->create([
        "user_id" => $customer->id,
    ]);

    $ticket = Ticket::factory()->create();

    expect(fn() => $ticket->addInternalNote($contact, "Note"))->toThrow(
        InvalidMessageException::class,
    );

    expect($ticket->messages()->count())->toBe(0);
});

it("rejects internal notes from agents outside the ticket group", function () {
    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $contact = Contact::factory()->create([
        "user_id" => $agent->id,
    ]);

    $group = Group::factory()->create();

    $ticket = Ticket::factory()->create([
        "group_id" => $group->id,
    ]);

    expect(fn() => $ticket->addInternalNote($contact, "Note"))->toThrow(
        InvalidMessageException::class,
    );

    expect($ticket->messages()->count())->toBe(0);
});

it("allows administrators to add internal notes", function () {
    Event::fake();

    $admin = User::factory()->create([
        "role" => UserRole::Admin,
    ]);

    $contact = Contact::factory()->create([
        "user_id" => $admin->id,
    ]);

    $ticket = Ticket::factory()->create();

    $message = $ticket->addInternalNote($contact, "Admin note");

    expect($message->isInternal())->toBeTrue();
});

it("should not resume not pending tickets", function (TicketStatus $status) {
    $ticket = Ticket::factory()->create([
        "status" => $status,
    ]);

    expect(fn() => $ticket->resume())->toThrow(InvalidTicketStateException::class);
})->with([
    "closed tickets should not be resumed" => [TicketStatus::Closed],
    "resolved tickets should not be resumed" => [TicketStatus::Resolved],
]);

it("should not reopen pending tickets", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Pending,
    ]);

    expect(fn() => $ticket->reopen())->toThrow(InvalidTicketStateException::class);
});

it("should not mark as pending not open tickets", function (
    TicketStatus $status,
) {
    $ticket = Ticket::factory()->create([
        "status" => $status,
    ]);

    expect(fn() => $ticket->pend())->toThrow(InvalidTicketStateException::class);
})->with([
    "resolved tickets cannot be pending" => [TicketStatus::Resolved],
    "closed tickets cannot be pending" => [TicketStatus::Closed],
]);

it(
    "should not allow assign an agent that doesn't belong to ticket's group",
    function () {
        $group = Group::factory()->create();
        $ticket = Ticket::factory()->create();
        $ticket->assignToGroup($group);
        $agent = User::factory()->create([
            "role" => UserRole::Agent,
        ]);

        Event::fake([TicketAssigned::class]);

        expect(fn() => $ticket->assignTo($agent))->toThrow(
            InvalidTicketAssignmentException::class,
        );

        expect($ticket->fresh()->assigned_id)->toBeNull();
        Event::assertNotDispatched(TicketAssigned::class);
    },
);

it("should not resolve a closed ticket", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Closed,
    ]);

    expect(fn() => $ticket->resolve())->toThrow(InvalidTicketStateException::class);
});

it("should not close a non resolved ticket", function (TicketStatus $status) {
    $ticket = Ticket::factory()->create([
        "status" => $status,
    ]);

    expect(fn() => $ticket->close())->toThrow(InvalidTicketStateException::class);
})->with([
    "open tickets cannot be closed" => [TicketStatus::Open],
    "pending tickets cannot be closed" => [TicketStatus::Pending],
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

    expect(fn() => $ticket->assignTo($user))->toThrow(InvalidTicketAssignmentException::class);
});

it("dispatches an event when a ticket is assigned", function () {
    $ticket = Ticket::factory()->create();

    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $group = Group::factory()->create();
    $group->addUser($agent);
    $ticket->assignToGroup($group);

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

    $group = Group::factory()->create();
    $group->addUser($agent);
    $ticket->assignToGroup($group);

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
    $contact = $ticket->requester;
    $channel = Channel::factory()->create();
    Event::fake([MessageSent::class]);

    $message = app(AddTicketMessage::class)->handle(
        $ticket,
        $contact,
        "I need help",
        MessageDirection::Inbound,
        $channel,
    );

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

it(
    "as group changes, removes the agent if they don't belong to that group",
    function () {
        $ticket = Ticket::factory()->create();
        $agent = User::factory()->create([
            "role" => UserRole::Agent,
        ]);
        $group = Group::factory()->create();
        $group->addUser($agent);
        $ticket->assignToGroup($group);
        $ticket->assignTo($agent);

        $newGroup = Group::factory()->create();

        $ticket->assignToGroup($newGroup);

        expect($ticket->fresh()->assigned_id)->toBeNull();
    },
);

it(
    "as group changes, keeps the agent if they belong to that group",
    function () {
        $ticket = Ticket::factory()->create();
        $agent = User::factory()->create([
            "role" => UserRole::Agent,
        ]);
        $group = Group::factory()->create();
        $group->addUser($agent);
        $ticket->assignToGroup($group);
        $ticket->assignTo($agent);

        $newGroup = Group::factory()->create();
        $newGroup->addUser($agent);

        $ticket->assignToGroup($newGroup);

        expect($ticket->fresh()->assigned_id)->toBe($agent->id);
    },
);

it("dispatches an event when an internal note is added", function () {
    $ticket = Ticket::factory()->create();
    $agent = User::factory()->create(["role" => UserRole::Agent]);
    $contact = Contact::factory()->create(["user_id" => $agent->id]);
    $group = Group::factory()->create();
    $group->addUser($agent);
    $ticket->assignToGroup($group);

    Event::fake([InternalNoteAdded::class]);

    $message = app(AddInternalNote::class)->handle(
        $ticket,
        $contact,
        "I need help",
    );

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
it("does not dispatch an event when adding the same group", function () {
    $group = Group::factory()->create();
    $ticket = Ticket::factory()->create([
        "group_id" => $group->id,
    ]);

    Event::fake([TicketGroupAssigned::class]);

    $ticket->assignToGroup($group);

    Event::assertNotDispatched(TicketGroupAssigned::class);
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

    $group = Group::factory()->create();
    $group->addUser($agent);

    $ticket = Ticket::factory()->create();

    $ticket->assignToGroup($group);
    $ticket->assignTo($agent);

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
