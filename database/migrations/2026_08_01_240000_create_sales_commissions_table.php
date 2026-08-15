<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('sales_amount', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['admin_id', 'period_start', 'period_end'], 'sc_admin_period_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_commissions');
    }
};
