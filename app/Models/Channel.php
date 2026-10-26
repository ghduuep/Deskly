<?php

namespace App\Models;

use App\ChannelType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(["name", "type", "external_id", "is_active"])]
class Channel extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            "type" => ChannelType::class,
            "is_active" => "boolean",
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
