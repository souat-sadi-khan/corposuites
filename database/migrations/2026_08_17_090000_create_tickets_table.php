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
        // Table is `tickets` — checked the Naming/Table Conflict Guard and
        // every existing migration first, no collision anywhere. This is
        // the Support module's primary entity, the same role Projects/
        // Assets/Vendors play for their own modules.
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('description');

            // Nullable at the DB level with nullOnDelete, but required in the
            // Form Request — the same deliberate split Asset Register uses
            // for asset_category_id: no ticket may be filed without a
            // category, but deleting a category must not take the ticket
            // register with it.
            $table->foreignId('ticket_category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();

            // Two independent, optional requester links (never a false
            // choice between them) plus free-text fallback contact details
            // for a ticket raised by neither an existing Sales customer nor
            // an HRM employee — e.g. an ad hoc walk-in or, later, a Customer
            // Portal submission with no linked record yet. Same "two
            // optional responsible parties" reasoning already used for
            // Asset Maintenance Schedule's vendor_id/assigned_to pair.
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('raised_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone')->nullable();

            // Fixed enums for now, the same "hardcoded enum now, retrofit a
            // configurable master + optional FK only on a real need" choice
            // already made for Chart of Accounts' account_type ahead of
            // Account Types — Ticket Status and Priority Management are
            // separate, not-yet-built roadmap items that will presumably
            // layer their own configurable masters on top of these, exactly
            // the way Account Types layered onto Chart of Accounts.
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('ticket_status', ['open', 'in_progress', 'on_hold', 'resolved', 'closed'])->default('open');

            $table->enum('source', ['web', 'email', 'phone', 'portal', 'walk_in'])->default('web');
            $table->date('due_date')->nullable();

            // Service-derived timestamps, not user-entered — stamped when the
            // ticket actually reaches that state, cleared if it is moved back
            // out, the same "completion consistency owned by the service"
            // pattern Project/Milestone/Task/Asset Disposal already use.
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
