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
        Schema::create('activity_logs', function (Blueprint $table) {

            $table->id();

            // Actor
            $table->string('actor_type')->nullable(); // admin,user,system
            $table->unsignedBigInteger('actor_id')->nullable();

            // Module
            $table->string('module')->nullable();

            // Action
            $table->string('action'); // create,update,delete,login etc

            // Target
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();

            // Description
            $table->text('description')->nullable();

            // Data
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();

            // Request info
            $table->string('ip_address',45)->nullable();
            $table->text('user_agent')->nullable();

            // Route info
            $table->string('url')->nullable();
            $table->string('method')->nullable();

            // Extra
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['actor_type','actor_id']);
            $table->index(['model','model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
