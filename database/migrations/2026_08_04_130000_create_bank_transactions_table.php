<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_bank_account_id')->constrained('finance_bank_accounts')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->enum('transaction_type', ['deposit', 'withdrawal']);
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->boolean('reconciled')->default(false);
            $table->date('reconciled_date')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
