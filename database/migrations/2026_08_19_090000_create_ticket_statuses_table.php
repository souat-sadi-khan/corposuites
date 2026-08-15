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
        // A configurable, finer-grained status master layered on top of the
        // fixed `tickets.ticket_status` enum — the exact same "hardcoded
        // enum now, retrofit a configurable master + optional FK only on a
        // real need" pattern Account Types already built on top of Chart of
        // Accounts' `account_type` enum. `tickets.ticket_status` stays as
        // the always-present, universal lifecycle bucket (open/in_progress/
        // on_hold/resolved/closed); this table lets an admin define named
        // sub-statuses (e.g. "Waiting on Customer", "Escalated") that each
        // map back to one of those five fixed buckets via `maps_to`.
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('maps_to', ['open', 'in_progress', 'on_hold', 'resolved', 'closed'])->default('open');
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
        Schema::dropIfExists('ticket_statuses');
    }
};
