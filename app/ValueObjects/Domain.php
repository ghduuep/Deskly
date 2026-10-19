<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class Domain
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (
            !filter_var(
                $normalized,
                FILTER_VALIDATE_DOMAIN,
                FILTER_FLAG_HOSTNAME,
            )
        ) {
            throw new InvalidArgumentException("Invalid domain.");
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
