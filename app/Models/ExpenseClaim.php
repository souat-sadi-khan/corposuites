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

    protected $fillable = [
        'employee_id', 'category', 'amount', 'expense_date', 'description',
        'receipt_path', 'approval_status', 'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function approvalPayload(): array
    {
        return [
            'employee' => $this->employee->full_name ?? null,
            'category' => $this->category,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
        ];
    }

    public function workflowModuleKey(): string
    {
        return 'expense_claim';
    }
}
