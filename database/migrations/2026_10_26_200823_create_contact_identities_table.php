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
        Schema::create("contact_identities", function (Blueprint $table) {
            $table->id();
            $table->foreignId("contact_id")->constrained()->cascadeOnDelete();
            $table->string("type");
            $table->string("identifier");
            $table->timestamps();

            $table->unique(["type", "identifier"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("contact_identities");
    }
};
