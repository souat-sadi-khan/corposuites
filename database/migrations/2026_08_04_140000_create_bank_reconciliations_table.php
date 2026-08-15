<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();

            $table->string('reconciliation_number')->unique();
            $table->foreignId('finance_bank_account_id')->constrained('finance_bank_accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->decimal('statement_opening_balance', 15, 2)->default(0);
            $table->decimal('statement_closing_balance', 15, 2)->default(0);
            $table->decimal('computed_closing_balance', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);
            $table->enum('reconciliation_status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('bank_transaction_id')->constrained('bank_transactions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bank_reconciliation_id', 'bank_transaction_id'], 'br_reconciliation_transaction_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
    }
};
