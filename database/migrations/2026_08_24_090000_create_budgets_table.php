<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();

            // Server-generated, immutable once issued (see BudgetService) —
            // same "issued once, never re-derived" precedent as
            // ProjectBudget::budget_code/Ticket::ticket_number.
            $table->string('budget_code')->unique();

            $table->string('title')->nullable();

            // Same period_type/period_start/period_end shape SalesTarget and
            // SalesCommission already established for a company-wide,
            // period-scoped record — reused here rather than inventing a
            // second convention for the same concept.
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('yearly');
            $table->date('period_start');
            $table->date('period_end');

            // Budgets are versioned per period (original, then revisions),
            // the same "versioned per parent scope" shape ProjectBudget
            // already established — just scoped by period here instead of
            // by project, since this is the company-wide budget, not a
            // per-project one.
            $table->unsignedSmallInteger('version')->default(1);

            // Server-computed only — summed from budget_items on every
            // save, never client-submitted.
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->enum('budget_status', ['draft', 'approved', 'revised', 'closed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->date('approved_date')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            // One version sequence per period — the same key a company would
            // naturally revise an existing period's budget under, rather
            // than a bare global version counter.
            $table->unique(['period_start', 'period_end', 'version'], 'budgets_period_version_unique');
        });

        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();

            // Required parent-line FK, cascadeOnDelete — the same accepted
            // data-integrity tradeoff already documented for
            // journal_entry_items.chart_of_account_id: deleting a Chart of
            // Accounts entry with budget history cascades away its budget
            // lines, and a stored budget total could technically end up out
            // of sync with its now-smaller set of surviving lines. This is
            // a known, accepted gap (same category as Journal Entries' own
            // note), not a new kind of risk introduced here.
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();

            $table->decimal('planned_amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            // Deliberately UNIQUE per (budget, account) — a genuine
            // divergence from Journal Entries' own line items, which allow
            // the same account to repeat across lines (a compound posting).
            // A budget line is a single planned figure per account per
            // budget document; a second line for the same account within
            // one budget would just be an accidental duplicate, not a real
            // use case the way a split journal posting is.
            $table->unique(['budget_id', 'chart_of_account_id'], 'budget_items_budget_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('budgets');
    }
};
