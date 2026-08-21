<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Retrofit for Minimum Wage & Compliance (Module 7): a minimum
            // wage rule is configured per country/state, so an employee
            // needs a location to resolve which rule applies to them.
            // Plain free-text strings, matching the existing city/country
            // precedent already used elsewhere in this project (e.g. Client)
            // rather than a countries master table.
            $table->string('country')->nullable()->after('address');
            $table->string('state')->nullable()->after('country');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['country', 'state']);
        });
    }
};
