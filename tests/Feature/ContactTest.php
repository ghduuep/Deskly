<?php

use App\Models\Company;
use App\Models\Contact;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\PhoneNumber;

it("can assign contact to a company", function () {
    $contact = Contact::factory()->create();
    $company = Company::factory()->create();

    $contact->assignToCompany($company);

    expect($contact->fresh()->company_id)->toBe($company->id);
});
