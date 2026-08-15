<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['admin_id', 'period_start', 'period_end'], 'st_admin_period_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_targets');
    }
};
