<?php

namespace Database\Factories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "disk" => "local",
            "path" => "attachments/" . Str::uuid() . ".pdf",
            "original_name" => "document.pdf",
            "mime_type" => "application/pdf",
            "size" => 1024,
            "uploaded_by_id" => null,
            "attachable_type" => null,
            "attachable_id" => null,
        ];
    }
}
