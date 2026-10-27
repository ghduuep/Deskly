<?php

namespace Database\Factories;

use App\Enum\MessageType;
use App\Models\Draft;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Draft>
 */
class DraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "ticket_id" => Ticket::factory()->create(),
            "user_id" => User::factory()->create(),
            "type" => MessageType::Reply,
            "content" => fake()->sentence(),
        ];
    }
}
