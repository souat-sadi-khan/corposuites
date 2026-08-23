<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Stored snapshot, same convention as attendance_deduction/
            // overtime_amount — for payslip transparency, computed once at
            // generation time from the loans matched by resolveLoanDeduction().
            $table->decimal('loan_deduction', 12, 2)->default(0)->after('attendance_deduction');
        });

        // Per-loan breakdown of what this payroll run actually deducted —
        // needed so PayrollService::delete() can reverse exactly the right
        // amount off exactly the right loan's paid_amount, and so the
        // payslip can list which loan(s) were paid down this period.
        Schema::create('payroll_loan_deductions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('employee_loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_loan_deductions');

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('loan_deduction');
        });
    }
};
