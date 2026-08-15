<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A project's own billing document — deliberately not a Sales Invoice.
     * Sales Invoices bill a customer against product-based sales documents
     * (Sales Orders); this bills a project's client for service-based work
     * (billable Time Entries and approved Project Expenses). Kept as its
     * own table so the two billing flows can never collide or be confused.
     */
    public function up(): void
    {
        Schema::create('project_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number')->unique();

            // A bill has no meaning without the project it was raised for.
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // subtotal/grand_total are server-computed only; discount_amount
            // and tax_amount are the two header inputs an admin can set.
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);

            $table->enum('invoice_status', ['draft', 'sent', 'partially_paid', 'paid', 'cancelled'])->default('draft');

            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('project_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_invoice_id')->constrained('project_invoices')->cascadeOnDelete();

            $table->enum('source_type', ['time_entry', 'expense', 'manual'])->default('manual');

            // Both optional and nullOnDelete — a line traced back to a real
            // Time Entry or Expense keeps its own billed figures even if
            // that source record is later removed; only a manual line has
            // neither set. Named unique indexes are a defense-in-depth
            // guard against double-billing the same entry/expense across
            // two different invoices — the real guard is the Form Request
            // excluding an already-billed source from the picker in the
            // first place (an invoice's own cancellation frees its sources
            // back up, checked via the invoice's own status, not a flag on
            // the source row).
            $table->foreignId('project_time_entry_id')->nullable()->constrained('project_time_entries')->nullOnDelete();
            $table->foreignId('project_expense_id')->nullable()->constrained('project_expenses')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();

            $table->unique('project_time_entry_id', 'pii_time_entry_unique');
            $table->unique('project_expense_id', 'pii_expense_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invoice_items');
        Schema::dropIfExists('project_invoices');
    }
};
