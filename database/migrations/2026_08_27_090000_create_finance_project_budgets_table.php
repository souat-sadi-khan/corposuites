<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named `finance_project_budgets`, NOT `project_budgets` — the exact
        // same "finance_" collision-avoidance prefix already established
        // for finance_bank_accounts (vs. HRM's own bank_accounts). Project
        // Management already owns `project_budgets`/`ProjectBudget` (a
        // per-project, category-enum-based *planning* budget, with no tie
        // to Chart of Accounts). This is the deliberately separate,
        // Budget & Finance-owned "Project Budget" reconciliation-layer
        // feature both Project Budgets' and Budget Planning's own
        // changelog entries already reserved as its own future table — a
        // project-scoped, ledger-account-based budget that can eventually
        // be compared against actual Journal Entries, the same reason
        // Budget Planning/Department Budget both exist.
        Schema::create('finance_project_budgets', function (Blueprint $table) {
            $table->id();

            // 'FPB-' prefix — deliberately distinct from Project
            // Management's own 'PBG-' (project_budgets) and this module's
            // 'BUD-'/'DBG-', so no two document types' reference numbers
            // are ever visually confused.
            $table->string('budget_code')->unique();

            // Required, cascadeOnDelete — the same reasoning
            // ProjectBudget.project_id and DepartmentBudget.department_id
            // both already use: a document specifically scoped to its
            // owning entity is meaningless without it, and is deleted
            // with it.
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('title')->nullable();

            // Same period_type/period_start/period_end shape Budget
            // Planning/Department Budget already established, kept
            // consistent across all three of this module's budget types
            // rather than mimicking Project Management's own single
            // budget_date — a project can span multiple fiscal periods,
            // each needing its own allocation.
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('yearly');
            $table->date('period_start');
            $table->date('period_end');

            // Versioned per (project, period) — the same "versioned per
            // parent scope" shape Department Budget uses per department +
            // period, extended here with the project as the scoping
            // dimension instead.
            $table->unsignedSmallInteger('version')->default(1);

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->enum('budget_status', ['draft', 'approved', 'revised', 'closed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->date('approved_date')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'period_start', 'period_end', 'version'], 'fin_proj_budgets_proj_period_version_unique');
        });

        Schema::create('finance_project_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_project_budget_id')->constrained('finance_project_budgets')->cascadeOnDelete();

            // Required parent-line FK, cascadeOnDelete — the same accepted
            // data-integrity tradeoff already documented for
            // budget_items/department_budget_items/journal_entry_items.
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();

            $table->decimal('planned_amount', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            // One planned figure per account per budget document — the same
            // deliberate divergence from Journal Entries' own line items
            // that budget_items/department_budget_items already established.
            $table->unique(['finance_project_budget_id', 'chart_of_account_id'], 'fin_proj_budget_items_budget_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_project_budget_items');
        Schema::dropIfExists('finance_project_budgets');
    }
};
