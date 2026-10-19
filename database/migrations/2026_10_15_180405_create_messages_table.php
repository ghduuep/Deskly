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
        Schema::create("messages", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("ticket_id")
                ->constrained("tickets")
                ->cascadeOnDelete();
            $table->foreignId("contact_id")->constrained("contacts");
            $table->longText("content");
            $table->boolean("is_internal")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("messages");
    }
};
