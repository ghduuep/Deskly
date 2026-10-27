<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("attachments", function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs("attachable");
            $table
                ->foreignId("uploaded_by_id")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->string("disk");
            $table->string("path");
            $table->string("original_name");
            $table->string("mime_type");
            $table->unsignedBigInteger("size");
            $table->timestamps();

            $table->unique(["disk", "path"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("attachments");
    }
};
