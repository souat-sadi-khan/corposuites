<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_test_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('sender_identity_id')->nullable();
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('status'); // success, failed
            $table->text('error_message')->nullable();
            $table->json('response')->nullable(); // raw provider response
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->foreign('provider_id')
                  ->references('id')
                  ->on('email_providers')
                  ->onDelete('cascade');

            $table->foreign('sender_identity_id')
                  ->references('id')
                  ->on('email_sender_identities')
                  ->onDelete('set null');

            $table->index('provider_id');
            $table->index('sender_identity_id');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_test_logs');
    }
};
