<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Traits\HasWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaim extends Model implements Approvable
{
    use HasFactory, HasWorkflow;

    public const REIMBURSEMENT_METHODS = ['cash', 'bank_transfer', 'cheque', 'card', 'other'];

    protected $fillable = [
        'employee_id', 'expense_category_id', 'category_legacy', 'amount', 'expense_date', 'description',
        'receipt_path', 'approval_status', 'payment_status', 'payment_date', 'reimbursement_method', 'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'payment_date' => 'date',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Named to avoid the raw `category_legacy` column ever being confused
     * with this relation — Eloquent would resolve a bare `category()`
     * against the (still-present) legacy string column anyway, so the
     * explicit `expenseCategory` name keeps the two unambiguous.
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getIsReimbursedAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function approvalPayload(): array
    {
        return [
            'employee' => $this->employee->full_name ?? null,
            'category' => $this->expenseCategory->name ?? $this->category_legacy,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
        ];
    }

    public function workflowModuleKey(): string
    {
        return 'expense_claim';
    }
}
