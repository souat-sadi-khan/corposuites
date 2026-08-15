<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_budgets', function (Blueprint $table) {
            $table->id();

            // Server-generated, immutable once issued — same "issued once,
            // never re-derived" precedent as Budget::budget_code (a DBG-
            // prefix, distinct from Budget Planning's own BUD-, so the two
            // document types' reference numbers are never confused).
            $table->string('budget_code')->unique();

            // Required — per the Naming/Table Conflict Guard, this FKs the
            // existing HRM `departments` table rather than creating a
            // second departments table. cascadeOnDelete mirrors
            // ProjectBudget's own choice for project_id: a document
            // specifically scoped to its owning entity is meaningless
            // without it, and is deleted with it — the same reasoning, just
            // applied to a department instead of a project.
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();

            $table->string('title')->nullable();

            // Same period_type/period_start/period_end shape Budget
            // Planning (and SalesTarget/SalesCommission before it) already
            // established for a period-scoped record.
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('yearly');
            $table->date('period_start');
            $table->date('period_end');

            // Versioned per (department, period) — the same "versioned per
            // parent scope" shape ProjectBudget uses per project and
            // Budget Planning uses per period, extended here with the
            // department as an additional scoping dimension so two
            // different departments can each independently start at v1 for
            // the exact same fiscal period.
            $table->unsignedSmallInteger('version')->default(1);

            // Server-computed only — summed from department_budget_items on
            // every save, never client-submitted.
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->enum('budget_status', ['draft', 'approved', 'revised', 'closed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->date('approved_date')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'period_start', 'period_end', 'version'], 'dept_budgets_dept_period_version_unique');
        });

        Schema::create('department_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_budget_id')->constrained('department_budgets')->cascadeOnDelete();

            // Required parent-line FK, cascadeOnDelete — the same accepted
            // data-integrity tradeoff already documented for
            // budget_items.chart_of_account_id/journal_entry_items.chart_of_account_id.
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();

            $table->decimal('planned_amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            // One planned figure per account per budget document — the
            // same deliberate divergence from Journal Entries' own
            // (repeat-allowed) line items that Budget Planning's own
            // budget_items table already established.
            $table->unique(['department_budget_id', 'chart_of_account_id'], 'dept_budget_items_budget_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_budget_items');
        Schema::dropIfExists('department_budgets');
    }
};
