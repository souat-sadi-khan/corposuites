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
        Schema::create('asset_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('title');
            $table->enum('maintenance_type', ['preventive', 'inspection', 'calibration', 'servicing', 'other'])->default('preventive');
            $table->enum('frequency', ['one_time', 'weekly', 'monthly', 'quarterly', 'half_yearly', 'yearly'])->default('monthly');
            $table->date('start_date');
            $table->date('last_performed_date')->nullable();
            // Always derived from start/last-performed + frequency by the
            // service — never accepted from the form.
            $table->date('next_due_date')->nullable();
            // External servicer and/or internal technician; the technician
            // FKs to the existing HRM `employees` table per the Naming/Table
            // Conflict Guard.
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->enum('schedule_status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->text('instructions')->nullable();
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
        Schema::dropIfExists('asset_maintenance_schedules');
    }
};
