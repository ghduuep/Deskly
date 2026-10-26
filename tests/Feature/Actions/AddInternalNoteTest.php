
<?php
use App\Actions\Ticket\AddTicketMessage;
use App\Events\Message\MessageSent;
use App\Events\Ticket\TicketPended;
use App\Events\Ticket\TicketReopened;
use App\Events\Ticket\TicketResumed;
use App\MessageDirection;
use App\MessageType;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Ticket;
use App\TicketStatus;
use Illuminate\Support\Facades\Event;

it("pends an open ticket after an outbound reply", function () {
    $ticket = Ticket::factory()->create();
    $author = Contact::factory()->create();
    $channel = Channel::factory()->create();

    Event::fake([TicketPended::class, MessageSent::class]);

    $message = app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Test message",
        MessageDirection::Outbound,
        $channel,
    );

    expect($ticket->fresh()->status)
        ->toBe(TicketStatus::Pending)
        ->and($message->type)
        ->toBe(MessageType::Reply)
        ->and($message->direction)
        ->toBe(MessageDirection::Outbound);

    Event::assertDispatchedOnce(TicketPended::class);
    Event::assertDispatchedOnce(MessageSent::class);
});

it("resumes a pending ticket after an inbound reply", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Pending,
    ]);

    $author = Contact::factory()->create();
    $channel = Channel::factory()->create();

    Event::fake([TicketResumed::class]);

    app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Test message",
        MessageDirection::Inbound,
        $channel,
    );

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);

    Event::assertDispatchedOnce(TicketResumed::class);
});

it("reopens a resolved ticket after an inbound reply", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Resolved,
    ]);

    $author = Contact::factory()->create();
    $channel = Channel::factory()->create();

    Event::fake([TicketReopened::class]);

    app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Test message",
        MessageDirection::Inbound,
        $channel,
    );

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);

    Event::assertDispatchedOnce(TicketReopened::class);
});

it("rejects outbound replies on resolved tickets", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Resolved,
    ]);

    $author = Contact::factory()->create();
    $channel = Channel::factory()->create();

    Event::fake([MessageSent::class]);

    expect(
        fn() => app(AddTicketMessage::class)->handle(
            $ticket,
            $author,
            "Test message",
            MessageDirection::Outbound,
            $channel,
        ),
    )->toThrow(DomainException::class);

    expect($ticket->fresh()->status)
        ->toBe(TicketStatus::Resolved)
        ->and($ticket->messages()->count())
        ->toBe(0);

    Event::assertNotDispatched(MessageSent::class);
});

it("rejects replies on closed tickets", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Closed,
    ]);

    $author = Contact::factory()->create();
    $channel = Channel::factory()->create();

    Event::fake([MessageSent::class]);

    expect(
        fn() => app(AddTicketMessage::class)->handle(
            $ticket,
            $author,
            "Test message",
            MessageDirection::Inbound,
            $channel,
        ),
    )->toThrow(DomainException::class);

    expect($ticket->messages()->count())
        ->toBe(0)
        ->and($ticket->fresh()->status)
        ->toBe(TicketStatus::Closed);

    Event::assertNotDispatched(MessageSent::class);
});

it(
    "does not change status for an inbound reply on an open ticket",
    function () {
        $ticket = Ticket::factory()->create();
        $author = Contact::factory()->create();
        $channel = Channel::factory()->create();

        Event::fake([
            TicketPended::class,
            TicketResumed::class,
            TicketReopened::class,
        ]);

        app(AddTicketMessage::class)->handle(
            $ticket,
            $author,
            "Test message",
            MessageDirection::Inbound,
            $channel,
        );

        expect($ticket->fresh()->status)->toBe(TicketStatus::Open);

        Event::assertNotDispatched(TicketPended::class);
        Event::assertNotDispatched(TicketResumed::class);
        Event::assertNotDispatched(TicketReopened::class);
    },
);

it("sanitizes malicious HTML while preserving formatting", function () {
    $ticket = Ticket::factory()->create();
    $author = Contact::factory()->create();
    $channel = Channel::factory()->create();

    $message = app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "<p>Hello <strong>world</strong></p><script>alert(1)</script>",
        MessageDirection::Inbound,
        $channel,
    );

    expect($message->fresh()->content)
        ->toContain("<strong>world</strong>")
        ->not->toContain("<script")
        ->not->toContain("alert(1)");
});

it("dispatches MessageSent exactly once", function () {
    $ticket = Ticket::factory()->create();
    $author = Contact::factory()->create();
    $channel = Channel::factory()->create();

    Event::fake([MessageSent::class]);

    $message = app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Test message",
        MessageDirection::Inbound,
        $channel,
    );

    Event::assertDispatched(
        MessageSent::class,
        fn(MessageSent $event) => $event->messageId === $message->id &&
            $event->ticketId === $ticket->id,
    );

    Event::assertDispatchedOnce(MessageSent::class);
});

