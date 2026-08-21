<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 'early_leave' — the employee worked more than the half-day
        // threshold but still checked out before the shift's full end
        // time. Distinct from 'half_day' (worked less than the
        // threshold), so both can be judged and, later, deducted
        // separately by Payroll.
        DB::statement("ALTER TABLE attendances MODIFY COLUMN attendance_status ENUM('present','absent','half_day','on_leave','late','early_leave') NOT NULL DEFAULT 'present'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN attendance_status ENUM('present','absent','half_day','on_leave','late') NOT NULL DEFAULT 'present'");
    }
};
