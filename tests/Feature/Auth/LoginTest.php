<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it("authenticates a user with valid credentials", function () {
    $user = User::factory()->create([
        "email" => "test@example.com",
        "password" => Hash::make("password123"),
    ]);

    $response = $this->postJson("/login", [
        "email" => "test@example.com",
        "password" => "password123",
    ]);

    $response
        ->assertOk()
        ->assertJsonPath("message", "Authenticated successfully.");

    $this->assertAuthenticatedAs($user, "web");
});

it("rejects invalid credentials", function () {
    User::factory()->create([
        "email" => "test@example.com",
        "password" => Hash::make("password123"),
    ]);

    $this->postJson("/login", [
        "email" => "test@example.com",
        "password" => "wrong-password",
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(["email"]);

    $this->assertGuest("web");
});

it("validates required credentials", function () {
    $this->postJson("/login", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(["email", "password"]);
});
