<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sender_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('email');
            $table->string('name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('provider_id')
                  ->references('id')
                  ->on('email_providers')
                  ->onDelete('cascade');

            $table->index('provider_id');
            $table->index('email');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sender_identities');
    }
};
