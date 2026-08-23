<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_loans', function (Blueprint $table) {
            // Per-loan opt-in for the automatic payroll deduction. Only takes
            // effect when the global "hrm_loan_deduction_enabled" HRM Setting
            // is also on — this is the per-loan half of that dynamic toggle.
            $table->boolean('deduct_from_salary')->default(true)->after('installment_amount');
        });
    }

    public function down()
    {
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropColumn('deduct_from_salary');
        });
    }
};
