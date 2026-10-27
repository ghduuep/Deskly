<?php

namespace Database\Factories;

use App\Enum\MessageDirection;
use App\Enum\MessageType;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "ticket_id" => Ticket::factory(),
            "contact_id" => Contact::factory(),
            "content" => fake()->sentence(),
            "type" => MessageType::Reply,
            "direction" => MessageDirection::Outbound,
        ];
    }
}
