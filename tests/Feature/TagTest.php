<?php

use App\Models\Contact;
use App\Models\Tag;
use App\Models\Ticket;

it("can belong to multiple tickets", function () {
    $tag = Tag::factory()->create();

    $tickets = Ticket::factory()->count(3)->create();

    foreach ($tickets as $ticket) {
        $ticket->addTag($tag);
    }

    expect($tag->fresh()->tickets)->toHaveCount(3);
});

it("can belong to multiple contacts", function () {
    $tag = Tag::factory()->create();

    $contacts = Contact::factory()->count(3)->create();

    foreach ($contacts as $contact) {
        $contact->addTag($tag);
    }

    expect($tag->fresh()->contacts)->toHaveCount(3);
});

it("can be attached to both tickets and contacts", function () {
    $tag = Tag::factory()->create();

    $ticket = Ticket::factory()->create();
    $contact = Contact::factory()->create();

    $ticket->addTag($tag);
    $contact->addTag($tag);

    expect($tag->fresh()->tickets)
        ->toHaveCount(1)
        ->and($tag->fresh()->contacts)
        ->toHaveCount(1);
});

it("does not duplicate tag associations", function () {
    $ticket = Ticket::factory()->create();
    $tag = Tag::factory()->create();

    $ticket->addTag($tag);
    $ticket->addTag($tag);

    expect($ticket->fresh()->tags)->toHaveCount(1);
});

it("can remove a tag from a ticket", function () {
    $tag = Tag::factory()->create();
    $ticket = Ticket::factory()->create();

    $ticket->addTag($tag);

    $ticket->removeTag($tag);

    expect($ticket->fresh()->tags)->toHaveCount(0);
});

it("can remove a tag from a contact", function () {
    $tag = Tag::factory()->create();
    $contact = Contact::factory()->create();

    $contact->addTag($tag);

    $contact->removeTag($tag);

    expect($contact->fresh()->tags)->toHaveCount(0);
});
