<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('customer_id')->constrained('admins')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });
    }
};
