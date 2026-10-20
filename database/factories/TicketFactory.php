<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Ticket;
use App\TicketPriority;
use App\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'title' => fake()->sentence(),
            'status' => TicketStatus::Open->value,
            'priority' => TicketPriority::Normal->value,
        ];
    }
}
