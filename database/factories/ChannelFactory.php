<?php

namespace Database\Factories;

use App\ChannelType;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => fake()->name(),
            "type" => ChannelType::Web,
            "external_id" => null,
            "is_active" => true,
        ];
    }
}
