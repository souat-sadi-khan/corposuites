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
        // One SLA policy per fixed priority bucket (low/medium/high/urgent)
        // — a straightforward 1:1 lookup, the same "exactly one row per
        // fixed enum value" shape as Account Types has to Chart of
        // Accounts' account_type, just with a real, single-column DB
        // uniqueness constraint since there's exactly one dimension to key
        // on here (no name/nature split needed the way Account Types has,
        // since a policy's whole purpose IS its priority scope).
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->unique();
            $table->decimal('response_time_hours', 6, 2);
            $table->decimal('resolution_time_hours', 6, 2);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
