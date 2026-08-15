<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('rate', 8, 4)->default(0);
            $table->enum('tax_type', ['exclusive', 'inclusive'])->default('exclusive');
            $table->enum('applies_to', ['sales', 'purchase', 'both'])->default('both');
            $table->foreignId('sales_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_compound')->default(false);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
