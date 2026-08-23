<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per individual check-in/check-out EVENT — supports an employee
 * punching in/out multiple times in one day (e.g. lunch break, a trip out
 * and back), which the single check_in/check_out pair on `attendances`
 * cannot represent on its own. `attendances` keeps meaning exactly what it
 * always has to every other module already built on top of it (Monthly
 * Sheet, Attendance Report, exports, Leave/Adjustment integration): one row
 * per employee per day, with `check_in` now specifically the FIRST check-in
 * of the day (still what late-detection judges) and `check_out` the LAST
 * check-out of the day (for "what time did they finally leave" display) —
 * this table is the detailed, per-session audit trail underneath that daily
 * summary, never a replacement for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('punch_type', ['check_in', 'check_out']);
            $table->dateTime('punched_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_punches');
    }
};
