<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Hash;

it("registers a new customer", function () {
    $response = $this->postJson("/register", [
        "name" => "Alice",
        "email" => "alice@example.com",
        "password" => "secure-password@!",
        "password_confirmation" => "secure-password@!",
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath("message", "Registered successfully.")
        ->assertJsonPath("user.email", "alice@example.com");

    $user = User::where("email", "alice@example.com")->firstOrFail();

    expect($user->role)->toBe(UserRole::Customer);

    expect(Hash::check("secure-password@!", $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user, "web");
});

it("creates an associated contact", function () {
    $this->postJson("/register", [
        "name" => "Alice",
        "email" => "alice@example.com",
        "password" => "secure-password@!",
        "password_confirmation" => "secure-password@!",
    ])->assertCreated();

    $user = User::where("email", "alice@example.com")->firstOrFail();

    expect($user->contact)
        ->not->toBeNull()
        ->and($user->contact->name)
        ->toBe("Alice");

    $this->assertDatabaseHas("contacts", [
        "user_id" => $user->id,
        "email" => "alice@example.com",
    ]);
});

it("rejects mistmatch passwords", function () {
    $this->postJson("/register", [
        "name" => "Alice",
        "email" => "alice@example.com",
        "password" => "secure-password@!",
        "password_confirmation" => "different-secure@!",
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(["password"]);
});
