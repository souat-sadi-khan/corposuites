<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['product_attribute_id', 'value']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attribute_values');
    }
};
