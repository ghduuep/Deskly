<?php

use App\Models\User;

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web');

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', $user->name)
        ->assertJsonPath('data.role', $user->role)
        ->assertJsonPath('data.email', $user->email);
});
