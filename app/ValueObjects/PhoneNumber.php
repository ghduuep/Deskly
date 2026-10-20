<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class PhoneNumber
{
    public string $value;

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
