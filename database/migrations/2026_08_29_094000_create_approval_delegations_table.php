<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_delegations', function (Blueprint $table) {
            $table->id();

            // While the delegator is away, their approval tasks are routed to the delegate.
            $table->foreignId('delegator_admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('delegate_admin_id')->constrained('admins')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['delegator_admin_id', 'status', 'starts_on', 'ends_on'], 'appr_deleg_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_delegations');
    }
};
