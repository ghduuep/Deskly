<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class Slug
{
    public string $value;

    public function __construct(string $value)
    {
        if (
            $value === "" ||
            strlen($value) > 255 ||
            preg_match("/^[a-z0-9]+(?:-[a-z0-9]+)*$/D", $value) !== 1
        ) {
            throw new InvalidArgumentException("Invalid slug.");
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
