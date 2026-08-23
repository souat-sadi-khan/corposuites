<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Traits\HasWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends Model implements Approvable
{
    use HasFactory, HasWorkflow;

    protected $fillable = [
        'employee_id', 'loan_amount', 'installments', 'installment_amount', 'deduct_from_salary',
        'paid_amount', 'start_date', 'reason', 'approval_status', 'status'
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'deduct_from_salary' => 'boolean',
        'start_date' => 'date',
        'status' => 'boolean',
    ];

    protected $appends = ['remaining_balance', 'is_fully_paid'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getRemainingBalanceAttribute()
    {
        return round($this->loan_amount - $this->paid_amount, 2);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining_balance <= 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function approvalPayload(): array
    {
        return [
            'employee' => $this->employee->full_name ?? null,
            'loan_amount' => $this->loan_amount,
            'installments' => $this->installments,
        ];
    }

    public function workflowModuleKey(): string
    {
        return 'employee_loan';
    }
}
