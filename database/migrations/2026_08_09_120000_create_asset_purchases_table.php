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
        Schema::create('asset_purchases', function (Blueprint $table) {
            $table->id();
            // One purchase record per asset — enforced at the DB level, not
            // just in validation, the same unique-FK one-to-one shape
            // `delivery_notes.delivery_id` already established.
            $table->foreignId('asset_id')->unique()->constrained('assets')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('additional_cost', 15, 2)->default(0);
            $table->date('warranty_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_purchases');
    }
};
