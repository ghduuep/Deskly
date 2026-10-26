<?php

use App\ContactIdentityType;
use App\Models\Contact;
use App\Models\ContactIdentity;

it("belongs to a contact", function () {
    $identity = ContactIdentity::factory()->create();

    expect($identity->contact)->toBeInstanceOf(Contact::class);
});

it("allows a contact to have multiple identities", function () {
    $contact = Contact::factory()->create();

    ContactIdentity::factory()->create([
        "contact_id" => $contact->id,
        "type" => ContactIdentityType::Email,
        "identifier" => "john@example.com",
    ]);

    ContactIdentity::factory()->create([
        "contact_id" => $contact->id,
        "type" => ContactIdentityType::Email,
        "identifier" => "john@company.com",
    ]);

    expect($contact->identities)->toHaveCount(2);
});

it("casts the identity type to an enum", function () {
    $identity = ContactIdentity::factory()->create();

    expect($identity->type)->toBe(ContactIdentityType::Email);
});
