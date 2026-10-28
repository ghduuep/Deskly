<?php

use App\Exceptions\Domain\InvalidGroupMemberException;
use App\Models\Group;
use App\Models\Ticket;
use App\Models\User;
use App\Enum\UserRole;

it("can have multiple users", function () {
    $group = Group::factory()->create();

    $users = User::factory()
        ->count(3)
        ->create(["role" => UserRole::Agent]);

    foreach ($users as $user) {
        $group->addUser($user);
    }

    expect($group->users)->toHaveCount(3);
});

it("allows a user to belong to multiple groups", function () {
    $user = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $groups = Group::factory()->count(2)->create();

    foreach ($groups as $group) {
        $group->addUser($user);
    }

    expect($user->groups)->toHaveCount(2);
});

it("does not duplicate group memberships", function () {
    $group = Group::factory()->create();

    $user = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $group->addUser($user);
    $group->addUser($user);

    expect($group->users()->count())->toBe(1);
});

it("does not allow customer to join groups", function () {
    $group = Group::factory()->create();

    $customer = User::factory()->create();

    expect(fn() => $group->addUser($customer))->toThrow(
        InvalidGroupMemberException::class,
    );

    expect($group->users()->count())->toBe(0);
});

it("can remove a user from a group", function () {
    $group = Group::factory()->create();

    $user = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $group->addUser($user);
    $group->removeUser($user);

    expect($group->hasUser($user))->toBeFalse();
});

it("unassigns tickets when an agent leaves their group", function () {
    $group = Group::factory()->create();

    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $group->addUser($agent);

    $ticket = Ticket::factory()->create();

    $ticket->assignToGroup($group);
    $ticket->assignTo($agent);

    $group->removeUser($agent);

    expect($group->hasUser($agent))
        ->toBeFalse()
        ->and($ticket->fresh()->group_id)
        ->toBe($group->id)
        ->and($ticket->fresh()->assigned_id)
        ->toBeNull();
});

it("preserves assignments in other groups", function () {
    $agent = User::factory()->create([
        "role" => UserRole::Agent,
    ]);

    $firstGroup = Group::factory()->create();
    $secondGroup = Group::factory()->create();

    $firstGroup->addUser($agent);
    $secondGroup->addUser($agent);

    $firstTicket = Ticket::factory()->create();
    $firstTicket->assignToGroup($firstGroup);
    $firstTicket->assignTo($agent);

    $secondTicket = Ticket::factory()->create();
    $secondTicket->assignToGroup($secondGroup);
    $secondTicket->assignTo($agent);

    $firstGroup->removeUser($agent);

    expect($firstTicket->fresh()->assigned_id)
        ->toBeNull()
        ->and($secondTicket->fresh()->assigned_id)
        ->toBe($agent->id)
        ->and($secondGroup->hasUser($agent))
        ->toBeTrue();
});
