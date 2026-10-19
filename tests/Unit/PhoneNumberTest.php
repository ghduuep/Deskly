<?php

use App\ValueObjects\PhoneNumber;
use InvalidArgumentException;

it("creates a valid E.164 phone number", function () {
    $phone = new PhoneNumber("+5516981940419");

    expect($phone->value)
        ->toBe("+5516981940419")
        ->and((string) $phone)
        ->toBe("+5516981940419");
});

it("normalizes phone number formatting", function (
    string $input,
    string $expected,
) {
    $phone = new PhoneNumber($input);

    expect($phone->value)->toBe($expected);
})->with([
    "spaces" => ["+55 16 98123 2913", "+5516981232913"],
    "parentheses" => ["+55 (16) 91235-9999", "+5516912359999"],
    "periods" => ["+55.16.99999-1234", "+5516999991234"],
]);

it("rejects invalid phone numbers", function (string $value) {
    new PhoneNumber($value);
})
    ->with([
        "empty" => "",
        "missing country prefix" => "5516981320900",
        "leading zero" => "+0123456789",
        "too short" => "+1",
        "too long" => "+5532132132132132312",
        "letters" => "+5516ABCD123",
        "multiple plus signs" => "++5516981941304",
    ])
    ->throws(InvalidArgumentException::class);

it("compares normalized phone numbers", function () {
    $first = new PhoneNumber("+55 16 981570011");
    $second = new PhoneNumber("+55 16 981570011");
    $third = new PhoneNumber("+55 19 91234 0012");

    expect($first->equals($second))
        ->toBeTrue()
        ->and($first->equals($third))
        ->toBeFalse();
});
