<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deliberately its own table, not HRM's `expense_claims` — that table is
     * an employee's personal reimbursement claim (Approvable, wired into the
     * Workflow Engine); this one is a project-scoped cost record. An
     * employee can still be attributed here (who incurred it), but this
     * table carries no reimbursement machinery of its own — an employee
     * wanting money back for an out-of-pocket project cost still files a
     * normal HRM Expense Claim, this just tracks the project's own spend.
     */
    public function up(): void
    {
        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();

            // A cost has no meaning without the project it was spent on.
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // Both optional — a cost may have been paid to an outside vendor,
            // incurred by an employee (attribution only, see note above), or
            // neither (e.g. a flat recurring project charge).
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('title');
            $table->enum('expense_category', ['labour', 'materials', 'equipment', 'subcontract', 'travel', 'software', 'other'])
                ->default('other');
            $table->date('expense_date');
            $table->decimal('amount', 15, 2)->default(0);

            // Whether this cost gets passed through to the client's bill —
            // the input Project Billing (the next module) will read.
            $table->boolean('is_billable')->default(true);

            $table->string('receipt_path')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();

            $table->text('description')->nullable();
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
        Schema::dropIfExists('project_expenses');
    }
};
