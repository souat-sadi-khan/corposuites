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
        // repeatedly in this project (Customer Groups -> customers.
        // customer_group_id, Account Types -> chart_of_accounts.
        // account_type_id). Nullable/nullOnDelete: a ticket can exist with
        // no custom status assigned (it still always has its fixed
        // ticket_status enum), and deleting a custom status must not delete
        // the tickets carrying it.
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('ticket_status_id')->nullable()->after('ticket_status')
                ->constrained('ticket_statuses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_status_id');
        });
    }
};
