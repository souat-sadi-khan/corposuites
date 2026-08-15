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
        // Follow-up retrofit, not an edit to the original tickets migration
        // — same "build the dependency, then retrofit the FK" pattern used
        // repeatedly in this project (most recently ticket_status_id ->
        // ticket_statuses). Nullable/nullOnDelete: a ticket can exist with
        // no custom priority assigned (it still always has its fixed
        // priority enum), and deleting a custom priority must not delete
        // the tickets carrying it.
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('ticket_priority_id')->nullable()->after('priority')
                ->constrained('ticket_priorities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_priority_id');
        });
    }
};
