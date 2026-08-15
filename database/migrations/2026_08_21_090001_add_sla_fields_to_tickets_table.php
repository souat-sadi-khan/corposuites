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
        // Follow-up retrofit, not an edit to the original tickets migration
        // — same "build the dependency, then retrofit the FK" pattern used
        // repeatedly in this project. `sla_policy_id` is purely for
        // traceability/audit (which policy governed this ticket) — it is
        // never user-selectable, only ever resolved automatically from the
        // ticket's own fixed `priority` enum, so nullOnDelete is enough:
        // deleting the policy loses the audit trail but never the ticket.
        // The two due-date columns are the real SLA targets, recomputed by
        // the service on every save (see TicketService::withDerivedFields).
        // `first_responded_at` is a plain fact stamped once, by a dedicated
        // "Record First Response" action, never recomputed.
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->after('ticket_priority_id')
                ->constrained('sla_policies')->nullOnDelete();
            $table->dateTime('first_response_due_at')->nullable()->after('sla_policy_id');
            $table->dateTime('resolution_due_at')->nullable()->after('first_response_due_at');
            $table->dateTime('first_responded_at')->nullable()->after('resolution_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sla_policy_id');
            $table->dropColumn(['first_response_due_at', 'resolution_due_at', 'first_responded_at']);
        });
    }
};
