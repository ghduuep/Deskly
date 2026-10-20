<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Represents an immutable email address value.
 *
 * Validates the email address format without modifying its value.
 */
final readonly class EmailAddress
{
    public string $value;

    /**
     * @throws InvalidArgumentException If the email address is invalid.
     */
    public function __construct(string $value)
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid e-mail address.');
        }

        $this->value = $value;
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
