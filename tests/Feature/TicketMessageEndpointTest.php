<?php

use App\Enum\MessageDirection;
use App\Enum\TicketStatus;
use App\Enum\UserRole;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Ticket;
use App\Models\User;

it('lets the requester send an inbound message', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs($ticket->requester->user)
        ->postJson("/api/tickets/{$ticket->id}/messages", [
            'content' => 'Hello',
        ])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Hello')
        ->assertJsonPath('data.is_internal', false)
        ->assertJsonPath('data.direction', MessageDirection::Inbound->value);
});

it('lets an agent reply and pends the ticket', function () {
    $ticket = Ticket::factory()->create();
    $agent = User::factory()->create(['role' => UserRole::Agent]);
    Contact::factory()->create(['user_id' => $agent->id]);

    $this->actingAs($agent)
        ->postJson("/api/tickets/{$ticket->id}/messages", [
            'content' => 'On it',
        ])
        ->assertCreated()
        ->assertJsonPath('data.direction', MessageDirection::Outbound->value);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Pending);
});

it('returns unprocessable when replying to a closed ticket', function () {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $this->actingAs($ticket->requester->user)
        ->postJson("/api/tickets/{$ticket->id}/messages", [
            'content' => 'Hello',
        ])
        ->assertUnprocessable();
});

it('hides internal notes from the requester', function () {
    $group = Group::factory()->create();
    $ticket = Ticket::factory()->create(['group_id' => $group->id]);
    $agent = User::factory()->create(['role' => UserRole::Agent]);
    Contact::factory()->create(['user_id' => $agent->id]);
    $group->addUser($agent);

    $this->actingAs($agent)
        ->postJson("/api/tickets/{$ticket->id}/notes", [
            'content' => 'Internal only',
        ])
        ->assertCreated()
        ->assertJsonPath('data.is_internal', true);

    $this->actingAs($agent)
        ->getJson("/api/tickets/{$ticket->id}/messages")
        ->assertJsonCount(1, 'data');

    $this->actingAs($ticket->requester->user)
        ->getJson("/api/tickets/{$ticket->id}/messages")
        ->assertJsonCount(0, 'data');
});
