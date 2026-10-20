<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Represents an immutable URL-friendly slug.
 *
 * Accepts lowercase letters, digits, and syngle hyphens
 * between alphanumeric characters, with a maximum length
 * of 255 characters.
 *
 * Example: "my-first-article".
 */
final readonly class Slug
{
    public string $value;

    /**
     * @throws InvalidArgumentException If the slug is invalid.
     */
    public function __construct(string $value)
    {
        if (
            $value === '' ||
            strlen($value) > 255 ||
            preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) !== 1
        ) {
            throw new InvalidArgumentException('Invalid slug.');
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
