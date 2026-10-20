<?php

use App\Models\Company;
use App\Models\Contact;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\PhoneNumber;

it("can change contact email", function () {
    $contact = Contact::factory()->create();

    $contact->changeEmail(new EmailAddress("new-email@example.com"));

    $email = $contact->fresh()->email;

    expect($email)
        ->toBeInstanceOf(EmailAddress::class)
        ->and($email->value)
        ->toBe("new-email@example.com");
});

it("can change contact phone", function () {
    $contact = Contact::factory()->create();

    $contact->changePhone(new PhoneNumber("+5516981570419"));

    $phone = $contact->fresh()->phone;

    expect($phone)
        ->toBeInstanceOf(PhoneNumber::class)
        ->and($phone->value)
        ->toBe("+5516981570419");
});

it("can assign contact to a company", function () {
    $contact = Contact::factory()->create();
    $company = Company::factory()->create();

    $contact->assignToCompany($company);

    expect($contact->fresh()->company_id)->toBe($company->id);
});
