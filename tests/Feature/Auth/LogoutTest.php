<?php

use App\Models\User;

it("logs out authenticated user", function () {
    $user = User::factory()->create();

    $this->actingAs($user, "web");

    $this->postJson("/logout")
        ->assertOk()
        ->assertJsonPath("message", "Logged out successfully.");

    $this->assertGuest("web");
});

it("rejects unauthenticated logout requests", function () {
    $this->postJson("/logout")->assertUnauthorized();

    $this->assertGuest("web");
});
