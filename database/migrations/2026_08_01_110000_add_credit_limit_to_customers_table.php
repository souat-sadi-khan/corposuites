<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('credit_limit_enabled')->default(false)->after('tax_number');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('credit_limit_enabled');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_limit_enabled', 'credit_limit']);
        });
    }
};
