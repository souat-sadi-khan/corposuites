<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Accrual (Phase B1) — how the annual entitlement (days_allowed) is granted.
            $table->enum('accrual_method', ['none', 'annual', 'monthly'])
                ->default('annual')
                ->after('days_allowed');

            // Carry-forward (Phase B2).
            $table->boolean('allow_carry_forward')->default(false)->after('accrual_method');
            $table->decimal('max_carry_forward', 6, 2)->nullable()->after('allow_carry_forward');
            $table->unsignedTinyInteger('carry_forward_expiry_months')->nullable()->after('max_carry_forward');

            // Eligibility (Phase B3).
            $table->unsignedInteger('min_service_days')->default(0)->after('carry_forward_expiry_months');
            $table->enum('applicable_gender', ['all', 'male', 'female', 'other'])
                ->default('all')
                ->after('min_service_days');
            $table->json('applicable_employee_type_ids')->nullable()->after('applicable_gender');
            $table->json('applicable_designation_ids')->nullable()->after('applicable_employee_type_ids');

            // Request rules (Phase B4).
            $table->unsignedInteger('min_notice_days')->default(0)->after('applicable_designation_ids');
            $table->unsignedInteger('max_consecutive_days')->nullable()->after('min_notice_days');
            $table->boolean('allow_half_day')->default(false)->after('max_consecutive_days');
            $table->boolean('requires_attachment')->default(false)->after('allow_half_day');
            $table->boolean('is_encashable')->default(false)->after('requires_attachment');
        });
    }

    public function down()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'accrual_method',
                'allow_carry_forward',
                'max_carry_forward',
                'carry_forward_expiry_months',
                'min_service_days',
                'applicable_gender',
                'applicable_employee_type_ids',
                'applicable_designation_ids',
                'min_notice_days',
                'max_consecutive_days',
                'allow_half_day',
                'requires_attachment',
                'is_encashable',
            ]);
        });
    }
};
