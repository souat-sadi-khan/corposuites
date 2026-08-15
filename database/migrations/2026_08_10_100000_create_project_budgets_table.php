<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A project's planned spend, broken down into budget lines by category.
     *
     * A project may carry more than one budget over its life (an original and
     * later revisions), so this is not a one-to-one child of `projects` —
     * uniqueness is on (project_id, version) instead.
     *
     * `total_amount` is a server-computed column (summed from the lines by
     * the service), not a user input and not an accessor: a budget is an
     * approved planning figure, so the total that was approved must not
     * silently change shape. Same reasoning as every other document total in
     * this project (e.g. sales_quotations.grand_total).
     *
     * Naming note: roadmap item 13 (Budget & Finance) also lists a "Project
     * Budget" feature. That module is company-wide budget planning and will
     * own its own tables; these two (`project_budgets` /
     * `project_budget_items`) belong to Project Management and are the
     * per-project planning figures the Project Profitability Reports will read.
     */
    public function up(): void
    {
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code')->unique(); // server-generated, immutable
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('title')->nullable();
            $table->date('budget_date');
            $table->decimal('total_amount', 15, 2)->default(0); // server-computed
            $table->enum('budget_status', ['draft', 'approved', 'revised', 'closed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->date('approved_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'version'], 'pbudget_project_version_unique');
        });

        Schema::create('project_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_budget_id')->constrained('project_budgets')->cascadeOnDelete();
            $table->enum('category', ['labour', 'materials', 'equipment', 'subcontract', 'travel', 'software', 'other'])->default('other');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budget_items');
        Schema::dropIfExists('project_budgets');
    }
};
