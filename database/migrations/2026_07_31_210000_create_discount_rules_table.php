<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 15, 2);
            $table->enum('scope_type', ['all', 'category', 'product'])->default('all');
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('discount_rules');
    }
};
