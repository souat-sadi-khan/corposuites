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
        Schema::create('project_timesheets', function (Blueprint $table) {
            $table->id();

            // A timesheet has no meaning without the employee it summarizes —
            // required parent-owns-child FK, cascadeOnDelete.
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->date('week_start_date');
            $table->date('week_end_date');

            // Both server-computed from the Time Entries linked to this
            // timesheet — never client-submitted.
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->decimal('billable_hours', 6, 2)->default(0);

            $table->enum('timesheet_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');

            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            // One timesheet per employee per week — also relied on by the
            // generate/regenerate service to find-or-create the header.
            $table->unique(['employee_id', 'week_start_date'], 'pts_employee_week_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_timesheets');
    }
};
