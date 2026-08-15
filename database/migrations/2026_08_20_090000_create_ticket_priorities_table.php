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
        // A configurable, finer-grained priority master layered on top of
        // the fixed `tickets.priority` enum — the same "hardcoded enum now,
        // retrofit a configurable master + optional FK only on a real need"
        // pattern Ticket Status already built on top of `ticket_status`
        // (itself mirroring Account Types on Chart of Accounts), predicted
        // explicitly in Ticket Creation's own changelog entry.
        // `tickets.priority` stays the always-present, universal urgency
        // bucket (low/medium/high/urgent); this table lets an admin define
        // named priority levels (e.g. "P1 - Critical Outage",
        // "P4 - Cosmetic") that each declare which of those four fixed
        // buckets they count as via `maps_to`.
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('maps_to', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('color')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
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
        Schema::dropIfExists('ticket_priorities');
    }
};
