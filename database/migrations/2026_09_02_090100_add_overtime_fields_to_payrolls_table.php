<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Snapshotted for transparency (the same reason
            // commission_sales_amount got its own column) — an admin
            // looking at a payslip should be able to see the overtime
            // hours/amount that went into total_earnings, not just a
            // number folded silently into the total.
            $table->decimal('overtime_hours', 6, 2)->default(0)->after('total_earnings');
            $table->decimal('overtime_amount', 12, 2)->default(0)->after('overtime_hours');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['overtime_hours', 'overtime_amount']);
        });
    }
};
