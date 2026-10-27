<?php

use App\Actions\Ticket\AddTicketMessage;
use App\Enum\MessageDirection;
use App\Enum\MessageType;
use App\Enum\TicketStatus;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Draft;
use App\Models\Ticket;
use App\Models\User;

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
    $author = $ticket->requester;

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

    $author = $ticket->requester;

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

it("transfers draft attachments to the sent message", function () {
    $agent = User::factory()->create([
        "role" => \App\Enum\UserRole::Agent,
    ]);

    $author = Contact::factory()->create([
        "user_id" => $agent->id,
    ]);

    $ticket = Ticket::factory()->create();

    $draft = Draft::factory()->create([
        "ticket_id" => $ticket->id,
        "user_id" => $agent->id,
        "type" => MessageType::Reply,
    ]);

    $attachment = \App\Models\Attachment::factory()->create([
        "uploaded_by_id" => $agent->id,
    ]);

    $attachment->attachTo($draft);

    $message = app(AddTicketMessage::class)->handle(
        $ticket,
        $author,
        "Reply with attachment",
        MessageDirection::Outbound,
        $this->channel,
        $draft,
    );

    expect($draft->fresh())
        ->toBeNull()
        ->and($message->attachments()->count())
        ->toBe(1)
        ->and($attachment->fresh()->attachable_type)
        ->toBe($message->getMorphClass())
        ->and($attachment->fresh()->attachable_id)
        ->toBe($message->id);
});

it("rejects a draft belonging to another user", function () {
    $ticket = Ticket::factory()->create();

    $author = $ticket->requester;

    $otherUser = User::factory()->create();

    $draft = Draft::factory()->create([
        "ticket_id" => $ticket->id,
        "user_id" => $otherUser->id,
        "type" => MessageType::Reply,
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

    expect($draft->fresh())
        ->not->toBeNull()
        ->and($ticket->messages()->count())
        ->toBe(0);
});

it("does not delete a draft belonging to another ticket", function () {
    $ticket = Ticket::factory()->create();
    $otherTicket = Ticket::factory()->create();
    $author = $ticket->requester;

    $draft = Draft::create([
        "ticket_id" => $otherTicket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Reply,
        "content" => "Another ticket's draft",
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
    )->toThrow(
        DomainException::class,
        "The draft does not belong to this user or ticket.",
    );

    $this->assertModelExists($draft);
    expect($ticket->messages()->count())->toBe(0);
});

it("does not delete a draft with a different message type", function () {
    $ticket = Ticket::factory()->create();
    $author = $ticket->requester;

    $draft = Draft::create([
        "ticket_id" => $ticket->id,
        "user_id" => $author->user_id,
        "type" => MessageType::Internal,
        "content" => "Internal draft",
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
    )->toThrow(
        DomainException::class,
        "The draft does not belong to this user or ticket.",
    );

    $this->assertModelExists($draft);
    expect($ticket->messages()->count())->toBe(0);
});

it("deletes only the selected draft", function () {
    $ticket = Ticket::factory()->create();
    $otherTicket = Ticket::factory()->create();
    $author = $ticket->requester;

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
