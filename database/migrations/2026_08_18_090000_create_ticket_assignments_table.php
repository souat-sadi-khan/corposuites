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
        // A history table, not a single "current agent" column on tickets —
        // every handover is kept as its own row, so a ticket's assignment
        // history survives the next reassignment. Same structural family as
        // `AssetAssignment` (one active row at a time, closed out before a
        // new one is opened), adapted here for "who is handling this
        // ticket" instead of "who is holding this asset".
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            // The agent is a system login (Admin), not an HRM Employee — the
            // same actor entity every other reviewer/approver FK in this
            // project uses (approved_by, created_by, cashier_id, ...), since
            // handling a ticket is done by whoever is logged in, not
            // necessarily someone tracked in the HR module.
            $table->foreignId('assigned_to')->constrained('admins')->cascadeOnDelete();
            $table->date('assigned_date');
            $table->enum('assignment_status', ['assigned', 'reassigned', 'cancelled'])->default('assigned');
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');
    }
};
