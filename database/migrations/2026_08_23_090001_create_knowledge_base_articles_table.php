<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Server-generated from the title once, at creation, and immutable
            // afterward (same "issued once, never re-derived" precedent as
            // Ticket::ticket_number/Asset::asset_code/Client::client_code) —
            // an article's URL identifier should not move under whatever
            // links to it just because the title was later edited.
            $table->string('slug')->unique();

            // Required in the Form Request (every article must be filed
            // somewhere) but nullable/nullOnDelete at the DB level — the
            // same deliberate split Asset Register uses for
            // asset_category_id: deleting a category must not delete the
            // articles filed under it.
            $table->foreignId('knowledge_base_category_id')->nullable()
                ->constrained('knowledge_base_categories')->nullOnDelete();

            // A genuinely optional cross-reference to the existing Support
            // Ticket Categories master — e.g. "articles relevant to Billing
            // tickets" — never required, purely for cross-linking.
            $table->foreignId('ticket_category_id')->nullable()
                ->constrained('ticket_categories')->nullOnDelete();

            // The admin who authored the article — attribution only, never
            // client-submitted, auto-set from the authenticated admin.
            $table->foreignId('authored_by')->nullable()
                ->constrained('admins')->nullOnDelete();

            $table->string('excerpt', 500)->nullable();
            $table->text('content');

            // internal: staff-only knowledge base. public: cleared for a
            // future customer-facing surface (the next roadmap item,
            // Customer Portal) to read — captured now as plain data since
            // it costs nothing today, the same forward-looking-but-unused
            // field precedent as Chart of Accounts' is_group ahead of
            // Journal Entries, or Bank Transactions' reconciled ahead of
            // Bank Reconciliation. Nothing in this task consumes it yet.
            $table->enum('visibility', ['internal', 'public'])->default('internal');

            $table->enum('article_status', ['draft', 'published', 'archived'])->default('draft');

            // Service-derived only (stamped on publish, cleared on
            // returning to draft) — see KnowledgeBaseArticleService.
            $table->dateTime('published_at')->nullable();

            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_articles');
    }
};
