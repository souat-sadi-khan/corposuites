<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Follow-up migration, not an edit to the original Time Entries
     * migration — same "build the dependency, then retrofit the FK"
     * pattern used repeatedly throughout this project (Customer Groups →
     * customers.customer_group_id, Sales Targets → sales_orders.assigned_to).
     * Nullable/nullOnDelete: an entry is a valid standalone record before it
     * is ever grouped into a week, and survives if its timesheet is deleted.
     */
    public function up(): void
    {
        Schema::table('project_time_entries', function (Blueprint $table) {
            $table->foreignId('project_timesheet_id')->nullable()->after('project_task_id')
                ->constrained('project_timesheets')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_timesheet_id');
        });
    }
};
