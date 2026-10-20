<?php

namespace App\Casts;

use App\ValueObjects\HexColor;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class HexColorCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): mixed {
        return $value === null ? null : new HexColor($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): mixed {
        if ($value === null) {
            return null;
        }

        return $value instanceof HexColor
            ? $value
            : new HexColor($value)->value;
    }
}
