
<?php
use App\Actions\Ticket\AddTicketMessage;
use App\MessageDirection;
use App\MessageType;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Draft;
use App\Models\Ticket;
use App\Models\User;
use App\TicketStatus;

beforeEach(function () {
    $this->channel = Channel::factory()->create();
});

it("creates a draft associated with a user and ticket", function () {
    $ticket = Ticket::factory()->create();
    $user = User::factory()->create();

    $draft = Draft::create([
        "ticket_id" => $ticket->id,
        "user_id" => $user->id,
        "type" => MessageType::Reply,
        "content" => "Unsaved message",
    ]);

    expect($draft->fresh())
        ->user_id->toBe($user->id)
        ->ticket_id->toBe($ticket->id)
        ->type->toBe(MessageType::Reply)
        ->content->toBe("Unsaved message");
});

it("allows different users to have drafts on the same ticket", function () {
    $ticket = Ticket::factory()->create();

    $users = User::factory()->count(2)->create();

    foreach ($users as $user) {
        Draft::create([
            "ticket_id" => $ticket->id,
            "user_id" => $user->id,
            "type" => MessageType::Reply,
            "content" => "Unsaved message",
        ]);
    }

    expect($ticket->drafts()->count())->toBe(2);
});

it("deletes the draft after successfully sending a reply", function () {
    $ticket = Ticket::factory()->create();
    $author = Contact::factory()->create();

    $draft = Draft::create([
        "ticket_id" => $ticket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Reply,
        "content" => "Unsaved message",
    ]);

    app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Sent message",
        MessageDirection::Inbound,
        $this->channel,
        $draft,
    );

    expect(Draft::find($draft->id))
        ->toBeNull()
        ->and($ticket->messages()->count())
        ->toBe(1);
});

it("preserves the draft when sending fails", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Closed,
    ]);

    $author = Contact::factory()->create();

    $draft = Draft::create([
        "ticket_id" => $ticket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Reply,
        "content" => "Unsaved message",
    ]);

    expect(
        fn() => app(AddTicketMessage::class)->handle(
            $ticket,
            $author,
            "Sent message",
            MessageDirection::Inbound,
            $this->channel,
            $draft,
        ),
    )->toThrow(DomainException::class);

    expect(Draft::find($draft->id))
        ->not->toBeNull()
        ->and($ticket->messages()->count())
        ->toBe(0);
});

it("does not delete another user's draft", function () {
    $ticket = Ticket::factory()->create();
    $author = Contact::factory()->create();
    $otherUser = User::factory()->create();

    $draft = Draft::create([
        "ticket_id" => $ticket->id,
        "user_id" => $otherUser->id,
        "type" => MessageType::Reply,
        "content" => "Another user's draft",
    ]);

    app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Sent message",
        MessageDirection::Inbound,
        $this->channel,
        $draft,
    );

    expect(Draft::find($draft->id))->not->toBeNull();
});

it("does not delete a draft belonging to another ticket", function () {
    $ticket = Ticket::factory()->create();
    $otherTicket = Ticket::factory()->create();
    $author = Contact::factory()->create();

    $draft = Draft::create([
        "ticket_id" => $otherTicket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Reply,
        "content" => "Another ticket's draft",
    ]);

    app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Sent message",
        MessageDirection::Inbound,
        $this->channel,
        $draft,
    );

    expect(Draft::find($draft->id))->not->toBeNull();
});

it("does not delete a draft with a different message type", function () {
    $ticket = Ticket::factory()->create();
    $author = Contact::factory()->create();

    $draft = Draft::create([
        "ticket_id" => $ticket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Internal,
        "content" => "Internal draft",
    ]);

    app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Sent message",
        MessageDirection::Inbound,
        $this->channel,
        $draft,
    );

    expect(Draft::find($draft->id))->not->toBeNull();
});

it("deletes only the selected draft", function () {
    $ticket = Ticket::factory()->create();
    $otherTicket = Ticket::factory()->create();
    $author = Contact::factory()->create();

    $selectedDraft = Draft::create([
        "ticket_id" => $ticket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Reply,
        "content" => "Selected draft",
    ]);

    $otherDraft = Draft::create([
        "ticket_id" => $otherTicket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Reply,
        "content" => "Other draft",
    ]);

    app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Sent message",
        MessageDirection::Inbound,
        $this->channel,
        $selectedDraft,
    );

    expect(Draft::find($selectedDraft->id))
        ->toBeNull()
        ->and(Draft::find($otherDraft->id))
        ->not->toBeNull();
});

