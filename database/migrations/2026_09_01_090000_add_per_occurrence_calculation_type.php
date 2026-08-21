<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add 'per_occurrence' to the calculation_type enum — a component
        // whose final amount is a rate multiplied by however many times an
        // event happened this pay period (e.g. "$10 per late day"), entered
        // when Payroll is generated rather than being a flat/percentage
        // figure fixed at salary-structure-creation time.
        DB::statement("ALTER TABLE salary_components MODIFY COLUMN calculation_type ENUM('fixed','percentage','per_occurrence') NOT NULL");

        Schema::table('payroll_items', function (Blueprint $table) {
            // The occurrence count actually used for a per_occurrence
            // component on this specific payroll run, kept alongside the
            // computed amount so an old payslip still shows "3 x $10 = $30"
            // rather than just the final figure. Null for fixed/percentage
            // items, which have no occurrence count.
            $table->unsignedInteger('occurrence_count')->nullable()->after('amount');
        });
    }

    public function down()
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn('occurrence_count');
        });

        DB::statement("ALTER TABLE salary_components MODIFY COLUMN calculation_type ENUM('fixed','percentage') NOT NULL");
    }
};
