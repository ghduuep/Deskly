<?php

namespace App\ValueObjects;

use InvalidArgumentException;
/**
 * Represents an immutable phone number in E.164 format.
 *
 * Accepts common formatting characters and normalizes the number before validating it.
 * It doesn't validate if the country code exists or the phone number is actually valid.
 *
 * Example: +55 (16) 98194-1322 becomes +5516981941322.
 */
final readonly class PhoneNumber
{
    public string $value;

    /**
     * @throws InvalidArgumentException If the phone number is invalid.
     */
    public function __construct(string $value)
    {
        $normalized = preg_replace("/[\s().-]/", "", $value);

        if (!preg_match("/^\+[1-9]\d{1,14}$/", $normalized)) {
            throw new InvalidArgumentException(
                "Phone number must use E.164 format.",
            );
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
