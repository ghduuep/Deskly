<?php

use App\ValueObjects\Slug;
use InvalidArgumentException;

it("creates a valid slug", function () {
    $slug = new Slug("customer-support");

    expect($slug->value)
        ->toBe("customer-support")
        ->and((string) $slug)
        ->toBe("customer-support");
});

it("accepts valid slug formats", function (string $value) {
    expect(new Slug($value))->toBeInstanceOf(Slug::class);
})->with([
    "single word" => "support",
    "multiple words" => "customer-support-ticket",
    "numbers" => "tickets-123",
    "only numbers" => "12345",
    "maximum length" => str_repeat("a", 255),
]);

it("rejects invalid slugs", function (string $value) {
    new Slug($value);
})
    ->with([
        "empty" => "",
        "uppercase" => "Customer-Support",
        "spaces" => "customer support",
        "underscore" => "custommer_support",
        "leading hyphen" => "-customer",
        "trailing hyphen" => "customer-",
        "consective hyphens" => "customer--support",
        "special characters" => "customer@support",
        "too long" => str_repeat("a", 256),
    ])
    ->throws(InvalidArgumentException::class);

it("compares slugs by value", function () {
    $first = new Slug("customer-support");
    $second = new Slug("customer-support");
    $third = new Slug("customer-test");

    expect($first->equals($second))
        ->toBeTrue()
        ->and($first->equals($third))
        ->toBeFalse();
});
