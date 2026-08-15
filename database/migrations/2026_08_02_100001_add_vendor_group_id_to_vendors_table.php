<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('vendor_group_id')->nullable()->after('vendor_code')->constrained('vendor_groups')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_group_id');
        });
    }
};
