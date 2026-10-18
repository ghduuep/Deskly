<?php

namespace Database\Factories;

use App\KnowledgeArticleStatus;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeArticle>
 */
class KnowledgeArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "knowledge_category_id" => KnowledgeCategory::factory(),
            "author_id" => User::factory(),
            "title" => fake()->title(),
            "slug" => fake()->slug(),
            "content" => fake()->sentence(),
            "status" => KnowledgeArticleStatus::Draft,
            "published_at" => null,
        ];
    }
}
