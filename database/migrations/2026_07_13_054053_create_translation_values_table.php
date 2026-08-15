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
        Schema::create('translation_values', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('language_id');
            $table->unsignedBigInteger('translation_key_id');

            $table->longText('value')->nullable();

            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
            $table->foreign('translation_key_id')->references('id')->on('translation_keys')->onDelete('cascade');
            $table->unique([
                'language_id',
                'translation_key_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_values');
    }
};
