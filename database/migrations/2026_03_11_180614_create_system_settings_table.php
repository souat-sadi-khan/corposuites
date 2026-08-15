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
        Schema::create('system_settings', function (Blueprint $table) {

            $table->id();

            // key name
            $table->string('key')->unique();

            // setting value
            $table->longText('value')->nullable();

            // grouping
            $table->string('group')->nullable();

            // auto load for cache
            $table->boolean('autoload')->default(true);

            $table->timestamps();

            $table->index(['group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
