<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('salary_structures', function (Blueprint $table) {
            $table->enum('pay_type', ['monthly', 'daily', 'commission'])
                ->default('monthly')
                ->after('employee_id');
        });
    }

    public function down()
    {
        Schema::table('salary_structures', function (Blueprint $table) {
            $table->dropColumn('pay_type');
        });
    }
};
