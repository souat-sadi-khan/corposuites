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
        // What to do when a ticket breaches its SLA — the natural next step
        // after SLA Management, reading the breach state that module's
        // is_response_breached/is_resolution_breached accessors already
        // compute. One rule per (priority, trigger) combination, enforced
        // with a real composite unique index, the same "exactly one
        // unambiguous lookup" reasoning SLA Policies uses for its own
        // single-column unique on `priority` — here two dimensions (which
        // priority, which breach type) both narrow the lookup, so both are
        // part of the key.
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->enum('trigger', ['response_breach', 'resolution_breach']);
            // Both actions are optional and independent — a rule can bump
            // priority only, reassign only, or both. FK to admins (a
            // system login), same actor entity Ticket Assignment already
            // uses for "who is handling this ticket".
            $table->foreignId('escalate_to_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('escalate_priority_to', ['low', 'medium', 'high', 'urgent'])->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['priority', 'trigger'], 'esc_rules_priority_trigger_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalation_rules');
    }
};
