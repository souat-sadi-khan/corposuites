<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First Support module table — checked the Naming/Table Conflict
        // Guard first, no `ticket_categories`/`support`-prefixed table
        // existed anywhere in the codebase. A plain flat lookup, the same
        // shape as Lead Sources/Employee Types — a category is a simple
        // classification label for tickets, nothing more.
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
