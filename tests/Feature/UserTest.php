<?php

use App\Models\User;
use App\UserRole;

it("should change user's role", function () {
    $user = User::factory()->create();

    $user->changeRole(UserRole::Admin);

    expect($user->fresh()->role)->toBe(UserRole::Admin);
});

it('determines wheter a user can be assigned tickets', function (
    UserRole $role,
    bool $expected,
) {
    $user = User::factory()->make([
        'role' => $role,
    ]);

    expect($user->canBeAssignedTickets())->toBe($expected);
})->with([
    'agents can receive tickets' => [UserRole::Agent, true],
    'admins can receive tickets' => [UserRole::Admin, true],
    'customer cannot receive tickets' => [UserRole::Customer, false],
]);
