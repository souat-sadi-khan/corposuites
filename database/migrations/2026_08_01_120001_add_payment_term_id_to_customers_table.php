<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('payment_term_id')->nullable()->after('customer_group_id')->constrained('payment_terms')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);
            $table->dropColumn('payment_term_id');
        });
    }
};
