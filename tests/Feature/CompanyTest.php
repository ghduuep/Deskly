<?php

use App\Models\Company;
use App\Models\Contact;
use App\ValueObjects\Domain;

it("should change company's domain", function () {
    $company = Company::factory()->create();

    $company->changeDomain(new Domain("titantelecom.com.br"));

    $domain = $company->fresh()->domain;

    expect($domain)
        ->toBeInstanceOf(Domain::class)
        ->and($domain->value)
        ->toBe("titantelecom.com.br");
});

it("should change company's name", function () {
    $company = Company::factory()->create();

    $company->changeName("Titan Telecomunicacoes LTDA");

    expect($company->fresh()->name)->toBe("Titan Telecomunicacoes LTDA");
});

it("should add a contact associated to it", function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->create();

    $company->addContact($contact);

    expect($contact->fresh()->company_id)->toBe($company->id);
});

it("should remove a contact associated to it", function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->create([
        "company_id" => $company->id,
    ]);

    $company->removeContact($contact);

    expect($contact->fresh()->company_id)->toBeNull();
});
