<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // The old free-text field is renamed (not dropped) so every
        // historical claim's original wording survives as a passive audit
        // trail, even though nothing reads from it going forward — the
        // dynamic expense_category_id FK below is the sole source of
        // truth from this point on.
        Schema::table('expense_claims', function (Blueprint $table) {
            $table->renameColumn('category', 'category_legacy');
        });

        Schema::table('expense_claims', function (Blueprint $table) {
            $table->string('category_legacy')->nullable()->change();

            // Nullable at the DB level so deleting a category can never
            // cascade away historical claims filed under it (the Form
            // Request requires it on create/update instead) — the same
            // split already used for assets.asset_category_id and
            // tickets.ticket_category_id.
            $table->foreignId('expense_category_id')->nullable()->after('employee_id')
                ->constrained('expense_categories')->nullOnDelete();

            // Reimbursement is a separate real-world event from approval —
            // an approved claim is not necessarily a paid-back one yet,
            // the same distinction Payroll already makes between a
            // generated payroll and payment_status = paid.
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid')->after('approval_status');
            $table->date('payment_date')->nullable()->after('payment_status');
            $table->enum('reimbursement_method', ['cash', 'bank_transfer', 'cheque', 'card', 'other'])
                ->nullable()->after('payment_date');
        });

        // Backfill: turn every distinct historical free-text category into
        // a real ExpenseCategory row, then point every existing claim's
        // new FK at the matching row — so no historical claim is ever left
        // without a real category once this migration finishes, and the
        // report can rely on the relation alone from day one.
        $distinctNames = DB::table('expense_claims')
            ->whereNotNull('category_legacy')
            ->distinct()
            ->pluck('category_legacy')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique();

        foreach ($distinctNames as $name) {
            $categoryId = DB::table('expense_categories')->where('name', $name)->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('expense_categories')->insertGetId([
                    'name' => $name,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('expense_claims')
                ->where('category_legacy', $name)
                ->update(['expense_category_id' => $categoryId]);
        }
    }

    public function down()
    {
        Schema::table('expense_claims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_category_id');
            $table->dropColumn(['payment_status', 'payment_date', 'reimbursement_method']);
        });

        Schema::table('expense_claims', function (Blueprint $table) {
            $table->renameColumn('category_legacy', 'category');
        });
    }
};
