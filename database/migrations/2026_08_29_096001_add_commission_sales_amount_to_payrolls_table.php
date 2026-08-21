<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Only used (and required) when the employee's active salary structure
            // is commission-based — the sales figure that period's commission is
            // calculated against. Left null for monthly/daily employees.
            $table->decimal('commission_sales_amount', 12, 2)->nullable()->after('year');
        });
    }

    public function down()
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('commission_sales_amount');
        });
    }
};
