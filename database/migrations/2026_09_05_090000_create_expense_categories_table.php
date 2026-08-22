<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            // Spending policy (enterprise expense control): a claim above
            // this amount is rejected outright; a claim above the receipt
            // threshold must carry a receipt. Both nullable = no limit.
            $table->decimal('max_amount_per_claim', 12, 2)->nullable();
            $table->decimal('receipt_required_above', 12, 2)->nullable();
            // Optional GL tie-in — which ledger account an approved claim
            // in this category would post against. No automatic journal
            // posting is done from this; it is mapping data only, for a
            // future accounting integration to read.
            $table->foreignId('chart_of_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_categories');
    }
};
