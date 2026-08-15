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
        Schema::create('asset_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            // Optional: unplanned repairs have no schedule behind them, and a
            // completed job stays a valid historical record even if the
            // schedule it came from is later removed.
            $table->foreignId('asset_maintenance_schedule_id')->nullable()
                ->constrained('asset_maintenance_schedules')->nullOnDelete();
            $table->string('title');
            $table->enum('maintenance_type', ['preventive', 'inspection', 'calibration', 'servicing', 'repair', 'other'])->default('preventive');
            $table->date('performed_date');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            // Internal technician — FKs to the existing HRM `employees` table
            // per the Naming/Table Conflict Guard.
            $table->foreignId('performed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('cost', 15, 2)->nullable();
            $table->decimal('downtime_hours', 8, 2)->nullable();
            $table->enum('record_status', ['in_progress', 'completed', 'cancelled'])->default('completed');
            $table->text('work_done')->nullable();
            $table->text('findings')->nullable();
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
        Schema::dropIfExists('asset_maintenance_records');
    }
};
