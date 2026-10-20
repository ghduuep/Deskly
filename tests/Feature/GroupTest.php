<?php

use App\Models\Group;
use App\Models\User;
use App\UserRole;

it('can have multiple users', function () {
    $group = Group::factory()->create();

    $users = User::factory()
        ->count(3)
        ->create(['role' => UserRole::Agent]);

    foreach ($users as $user) {
        $group->addUser($user);
    }

    expect($group->users)->toHaveCount(3);
});

it('allows a user to belong to multiple groups', function () {
    $user = User::factory()->create([
        'role' => UserRole::Agent,
    ]);

    $groups = Group::factory()->count(2)->create();

    foreach ($groups as $group) {
        $group->addUser($user);
    }

    expect($user->groups)->toHaveCount(2);
});

it('does not duplicate group memberships', function () {
    $group = Group::factory()->create();

    $user = User::factory()->create([
        'role' => UserRole::Agent,
    ]);

    $group->addUser($user);
    $group->addUser($user);

    expect($group->users()->count())->toBe(1);
});

it('does not allow customer to join groups', function () {
    $group = Group::factory()->create();

    $customer = User::factory()->create();

    expect(fn () => $group->addUser($customer))->toThrow(DomainException::class);

    expect($group->users()->count())->toBe(0);
});

it('can remove a user from a group', function () {
    $group = Group::factory()->create();

    $user = User::factory()->create([
        'role' => UserRole::Agent,
    ]);

    $group->addUser($user);
    $group->removeUser($user);

    expect($group->hasUser($user))->toBeFalse();
});
