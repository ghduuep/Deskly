<?php

namespace Database\Factories;

use App\Models\KnowledgeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeCategory>
 */
class KnowledgeCategoryFactory extends Factory
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
            "slug" => fake()->slug(),
            "description" => fake()->sentence(),
            "parent_id" => null,
        ];
    }
}
