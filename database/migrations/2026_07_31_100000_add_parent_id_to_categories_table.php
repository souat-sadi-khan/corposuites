<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            $table->unique(['parent_id', 'name']);
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['parent_id', 'name']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->unique('name');
        });
    }
};
