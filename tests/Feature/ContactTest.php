<?php

use App\Models\Company;
use App\Models\Contact;

it("can change contact email", function () {
    $contact = Contact::factory()->create();

    $contact->changeEmail("new-email@example.com");

    expect($contact->fresh()->email)->toBe("new-email@example.com");
});

it("can change contact phone", function () {
    $contact = Contact::factory()->create();

    $contact->changePhone("16981570419");

    expect($contact->fresh()->phone)->toBe("16981570419");
});

it("can assign contact to a company", function () {
    $contact = Contact::factory()->create();
    $company = Company::factory()->create();

    $contact->assignToCompany($company);

    expect($contact->fresh()->company_id)->toBe($company->id);
});
