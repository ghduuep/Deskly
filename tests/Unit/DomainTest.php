<?php

use App\ValueObjects\Domain;
use InvalidArgumentException;

it("creates a valid domain", function () {
    $domain = new Domain("example.com");

    expect($domain->value)
        ->toBe("example.com")
        ->and((string) $domain)
        ->toBe("example.com");
});

it("normalizes domains", function () {
    $domain = new Domain("   EXAMPLE.COM   ");

    expect($domain->value)->toBe("example.com");
});

it("rejects invalid domains", function (string $value) {
    new Domain($value);
})
    ->with([
        "empty" => "",
        "spaces inside domain" => "exa mple.com",
        "invalid characters" => "!example.com",
        "url instead of domain" => "https://example.com",
        "leading hyphen" => "-example.com",
        "trailing hyphen" => "example-.com",
    ])
    ->throws(InvalidArgumentException::class);

it("compares normalized domains", function () {
    $first = new Domain("example.com");
    $second = new Domain("example.com");
    $third = new Domain("new.com");

    expect($first->equals($second))
        ->toBeTrue()
        ->and($first->equals($third))
        ->toBeFalse();
});
