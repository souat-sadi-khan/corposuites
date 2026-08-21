<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('leave_request_id')->nullable()->after('employee_id')->constrained('leave_requests')->nullOnDelete();
            $table->string('leave_original_status')->nullable()->after('attendance_status');
            $table->text('leave_original_remarks')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leave_request_id');
            $table->dropColumn(['leave_original_status', 'leave_original_remarks']);
        });
    }
};
