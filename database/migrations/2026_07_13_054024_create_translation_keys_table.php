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
        Schema::create('translation_keys', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('group_id');

            $table->string('key');
            $table->string('description')->nullable();

            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('translation_groups')->onDelete('cascade');
            $table->unique([
                'group_id',
                'key'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_keys');
    }
};
