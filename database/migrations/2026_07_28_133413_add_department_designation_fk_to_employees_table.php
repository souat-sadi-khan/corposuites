<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('department')->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->after('designation')->constrained('designations')->nullOnDelete();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['department', 'designation']);
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('designation_id');
        });
    }
};
