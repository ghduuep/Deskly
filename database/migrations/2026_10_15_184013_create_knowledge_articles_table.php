<?php

use App\ArticleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("knowledge_articles", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("knowledge_category_id")
                ->constrained()
                ->restrictOnDelete();
            $table
                ->foreignId("author_id")
                ->constrained("users")
                ->restrictOnDelete();
            $table->string("title");
            $table->string("slug")->unique();
            $table->longText("content");
            $table->string("status")->default(ArticleStatus::Draft->value);
            $table->timestamp("published_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("knowledge_articles");
    }
};
