
<?php
use App\Actions\Ticket\AddInternalNote;
use App\Enum\MessageType;
use App\Enum\TicketStatus;
use App\Enum\UserRole;
use App\Models\Attachment;
use App\Models\Contact;
use App\Models\Draft;
use App\Models\Ticket;
use App\Models\User;

it("moves draft attachments to an internal note", function () {
    $ticket = Ticket::factory()->create();

    $user = User::factory()->create([
        "role" => UserRole::Admin,
    ]);

    $author = Contact::factory()->create([
        "user_id" => $user->id,
    ]);

    $draft = Draft::factory()->create([
        "ticket_id" => $ticket->id,
        "user_id" => $user->id,
        "type" => MessageType::Internal,
    ]);

    $attachment = Attachment::factory()->create([
        "uploaded_by_id" => $user->id,
    ]);

    $attachment->attachTo($draft);

    $message = app(AddInternalNote::class)->handle(
        $ticket,
        $author,
        "Internal note",
        $draft,
    );

    expect($message->type)
        ->toBe(MessageType::Internal)
        ->and($attachment->fresh()->attachable_type)
        ->toBe($message->getMorphClass())
        ->and($attachment->fresh()->attachable_id)
        ->toBe($message->id)
        ->and(Draft::find($draft->id))
        ->toBeNull();
});

it("rejects a draft owned by another user", function () {
    $ticket = Ticket::factory()->create();

    $user = User::factory()->create([
        "role" => UserRole::Admin,
    ]);

    $otherUser = User::factory()->create();

    $author = Contact::factory()->create([
        "user_id" => $user->id,
    ]);

    $draft = Draft::factory()->create([
        "ticket_id" => $ticket->id,
        "user_id" => $otherUser->id,
        "type" => MessageType::Internal,
    ]);

    expect(
        fn() => app(AddInternalNote::class)->handle(
            $ticket,
            $author,
            "Internal note",
            $draft,
        ),
    )->toThrow(DomainException::class);

    expect($ticket->messages()->count())
        ->toBe(0)
        ->and($draft->fresh())
        ->not->toBeNull();
});

it("rejects a reply draft for an internal note", function () {
    $ticket = Ticket::factory()->create();

    $user = User::factory()->create([
        "role" => UserRole::Admin,
    ]);

    $author = Contact::factory()->create([
        "user_id" => $user->id,
    ]);

    $draft = Draft::factory()->create([
        "ticket_id" => $ticket->id,
        "user_id" => $user->id,
        "type" => MessageType::Reply,
    ]);

    expect(
        fn() => app(AddInternalNote::class)->handle(
            $ticket,
            $author,
            "Internal note",
            $draft,
        ),
    )->toThrow(DomainException::class);

    expect($ticket->messages()->count())
        ->toBe(0)
        ->and($draft->fresh())
        ->not->toBeNull();
});

it("rejects a draft belonging to another ticket", function () {
    $ticket = Ticket::factory()->create();
    $otherTicket = Ticket::factory()->create();

    $user = User::factory()->create([
        "role" => UserRole::Admin,
    ]);

    $author = Contact::factory()->create([
        "user_id" => $user->id,
    ]);

    $draft = Draft::factory()->create([
        "ticket_id" => $otherTicket->id,
        "user_id" => $user->id,
        "type" => MessageType::Internal,
    ]);

    expect(
        fn() => app(AddInternalNote::class)->handle(
            $ticket,
            $author,
            "Internal note",
            $draft,
        ),
    )->toThrow(DomainException::class);

    expect($ticket->messages()->count())
        ->toBe(0)
        ->and($draft->fresh())
        ->not->toBeNull();
});

it("preserves the draft when internal note creation fails", function () {
    $ticket = Ticket::factory()->create([
        "status" => TicketStatus::Closed,
    ]);

    $user = User::factory()->create([
        "role" => UserRole::Admin,
    ]);

    $author = Contact::factory()->create([
        "user_id" => $user->id,
    ]);

    $draft = Draft::factory()->create([
        "ticket_id" => $ticket->id,
        "user_id" => $user->id,
        "type" => MessageType::Internal,
    ]);

    $attachment = Attachment::factory()->create([
        "uploaded_by_id" => $user->id,
    ]);

    $attachment->attachTo($draft);

    expect(
        fn() => app(AddInternalNote::class)->handle(
            $ticket,
            $author,
            "Internal note",
            $draft,
        ),
    )->toThrow(DomainException::class);

    expect($ticket->messages()->count())
        ->toBe(0)
        ->and($draft->fresh())
        ->not->toBeNull()
        ->and($attachment->fresh()->attachable_type)
        ->toBe($draft->getMorphClass())
        ->and($attachment->fresh()->attachable_id)
        ->toBe($draft->id);
});

it("creates an internal note without a draft", function () {
    $ticket = Ticket::factory()->create();

    $user = User::factory()->create([
        "role" => UserRole::Admin,
    ]);

    $author = Contact::factory()->create([
        "user_id" => $user->id,
    ]);

    $message = app(AddInternalNote::class)->handle(
        $ticket,
        $author,
        "Internal note without attachments",
    );

    expect($message->type)
        ->toBe(MessageType::Internal)
        ->and($message->ticket_id)
        ->toBe($ticket->id)
        ->and($message->attachments()->count())
        ->toBe(0)
        ->and($ticket->messages()->count())
        ->toBe(1);
});

