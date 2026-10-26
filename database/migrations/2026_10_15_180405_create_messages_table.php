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
            $table
                ->foreignId("channel_id")
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->longText("content");
            $table->string("type");
            $table->string("direction")->nullable();
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
