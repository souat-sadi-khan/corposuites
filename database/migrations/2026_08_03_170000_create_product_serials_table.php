<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('serial_number');
            $table->enum('serial_status', ['in_stock', 'sold', 'defective', 'returned'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'serial_number'], 'ps_product_serial_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_serials');
    }
};
