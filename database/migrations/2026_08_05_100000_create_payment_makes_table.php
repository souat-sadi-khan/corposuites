<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_makes', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('finance_bank_account_id')->nullable()->constrained('finance_bank_accounts')->nullOnDelete();
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'card', 'other'])->default('cash');
            $table->string('reference')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_make_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_make_id')->constrained('payment_makes')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->decimal('amount_allocated', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_make_allocations');
        Schema::dropIfExists('payment_makes');
    }
};
