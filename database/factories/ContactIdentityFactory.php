<?php

namespace Database\Factories;

use App\Enum\ContactIdentityType;
use App\Models\Contact;
use App\Models\ContactIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactIdentity>
 */
class ContactIdentityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "contact_id" => Contact::factory(),
            "type" => ContactIdentityType::Email,
            "identifier" => fake()->unique()->safeEmail(),
        ];
    }
}
