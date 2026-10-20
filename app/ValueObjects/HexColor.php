<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Represents an immutable hexadecimal RGB color.
 *
 * Accepts six-digit hexadecimal color prefixed with '#'.
 * Normalizes the value to uppercase.
 *
 * Example: #ff5733 becomes #FF5733.
 */
final readonly class HexColor
{
    public string $value;

    /**
     * @throws InvalidArgumentException If the hex color value is invalid.
     */
    public function __construct(string $value)
    {
        if (!preg_match("/^#[0-9a-fA-F]{6}$/D", $value)) {
            throw new InvalidArgumentException("Invalid hexadecimal color.");
        }

        $this->value = strtoupper($value);
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
