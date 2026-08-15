<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "SMTP", "SendGrid"
            $table->string('type'); // smtp, sendgrid, mailgun, ses, resend, postmark, brevo, custom_api
            $table->json('config')->nullable(); // flexible provider-specific settings (host, port, encryption, api_key, etc.)
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('sandbox_mode')->default(false);
            $table->boolean('maintenance_mode')->default(false);
            $table->string('health_status')->default('unknown'); // healthy, unhealthy, unknown
            $table->timestamp('last_health_check_at')->nullable();
            $table->unsignedInteger('timeout')->nullable(); // in seconds
            $table->softDeletes();
            $table->timestamps();

            // indexes for performance
            $table->index('type');
            $table->index('is_enabled');
            $table->index('is_default');
            $table->index('health_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_providers');
    }
};
