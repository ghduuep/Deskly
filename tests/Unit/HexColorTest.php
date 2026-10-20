<?php

use App\ValueObjects\HexColor;
use InvalidArgumentException;

it("creates a valid hexadecimal color", function () {
    $color = new HexColor("#FF5733");

    expect($color->value)
        ->toBe("#FF5733")
        ->and((string) $color)
        ->toBe("#FF5733");
});

it("normalizes hexadecimal colors to uppercase", function () {
    $color = new HexColor("#ff5733");

    expect($color->value)->toBe("#FF5733");
});

it("accepts valid hexadecimal colors", function (string $value) {
    expect(new HexColor($value))->toBeInstanceOf(HexColor::class);
})->with([
    "black" => "#000000",
    "white" => "#FFFFFF",
    "lowercase" => "#ffbbee",
    "mixed case" => "#FFbbaa",
]);

it("rejects invalid hexadecimal colors", function (string $value) {
    new HexColor($value);
})
    ->with([
        "empty" => "",
        "missing hash" => "FFFFFF",
        "short hex" => "#FFF",
        "eigh digit hex" => "#FFFFFFF",
        "invalid characters" => "#GGGGGG",
        "too short" => "#12345",
        "too long" => "#1234567",
        "whitespace" => " #FFFFFF",
    ])
    ->throws(InvalidArgumentException::class);

it("compares normalized hexadecimal colors", function () {
    $first = new HexColor("#FFFFFF");
    $second = new HexColor("#FFFFFF");
    $third = new HexColor("#000000");

    expect($first->equals($second))
        ->toBeTrue()
        ->and($first->equals($third))
        ->toBeFalse();
});
