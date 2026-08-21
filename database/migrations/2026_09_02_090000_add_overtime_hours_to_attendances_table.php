<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Hours worked beyond the shift on this specific day, entered
            // per attendance record. Payroll sums this across the pay
            // period and prices it using whichever overtime calculation
            // method is configured in HRM Settings.
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('attendance_status');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('overtime_hours');
        });
    }
};
