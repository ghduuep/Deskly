<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class HexColor
{
    public string $value;

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
