<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('reorder_level', 15, 2)->default(0);
            $table->decimal('reorder_quantity', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id'], 'rl_product_warehouse_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_levels');
    }
};
