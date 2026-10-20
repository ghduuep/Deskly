<?php

use App\ValueObjects\EmailAddress;
use InvalidArgumentException;

it("creates a valid e-mail address", function () {
    $email = new EmailAddress("ghduep@example.com");

    expect($email->value)
        ->toBe("ghduep@example.com")
        ->and((string) $email)
        ->toBe("ghduep@example.com");
});

it("rejects invalid email addresses", function (string $value) {
    new EmailAddress($value);
})
    ->with([
        "empty" => "",
        "missing at sign" => "ghduepexample.com",
        "missingdomain" => "ghduep@",
        "missing local part" => "@example.com",
        "spaces" => "ghd uep@example.com",
        "double at sign" => "ghduep@@example.com",
    ])
    ->throws(InvalidArgumentException::class);

it("compares email addresses by value", function () {
    $first = new EmailAddress("ghduep@example.com");
    $second = new EmailAddress("ghduep@example.com");
    $third = new EmailAddress("jane@example.com");

    expect($first->equals($second))
        ->toBeTrue()
        ->and($first->equals($third))
        ->toBeFalse();
});

it("preserves email address casing", function () {
    $email = new EmailAddress("Ghduep@example.com");

    expect($email->value)->toBe("Ghduep@example.com");
});
