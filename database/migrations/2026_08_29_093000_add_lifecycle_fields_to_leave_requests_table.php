<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add 'cancelled' to the approval_status enum (Phase D3).
        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN approval_status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('leave_requests', function (Blueprint $table) {
            // Half-day leave (Phase D1).
            $table->enum('duration_type', ['full_day', 'half_day'])->default('full_day')->after('end_date');
            $table->enum('half_day_session', ['first_half', 'second_half'])->nullable()->after('duration_type');

            // Supporting document (Phase D4).
            $table->string('attachment')->nullable()->after('reason');

            // Cancellation tracking (Phase D3).
            $table->text('cancellation_reason')->nullable()->after('approval_status');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn([
                'duration_type', 'half_day_session', 'attachment',
                'cancellation_reason', 'cancelled_at',
            ]);
        });

        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    }
};
