<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Combined late + early-leave + unapproved-absence deduction
            // for this period, snapshotted for transparency on the
            // payslip (same reasoning as overtime_amount).
            $table->decimal('attendance_deduction', 12, 2)->default(0)->after('overtime_amount');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('attendance_deduction');
        });
    }
};
