<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            // Portion of allocated_days that was carried forward from the previous year,
            // and the date that carried portion expires (Phase C3). Carried days are
            // treated as consumed last, so any unused remainder can be forfeited on expiry.
            $table->decimal('carried_days', 6, 2)->default(0)->after('used_days');
            $table->date('carry_expires_on')->nullable()->after('carried_days');
        });
    }

    public function down()
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn(['carried_days', 'carry_expires_on']);
        });
    }
};
