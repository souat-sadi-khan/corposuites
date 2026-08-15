<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Traits\HasWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model implements Approvable
{
    use HasFactory, HasWorkflow;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date',
        'total_days', 'reason', 'approval_status', 'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function approvalPayload(): array
    {
        return [
            'employee' => $this->employee->full_name ?? null,
            'leave_type' => $this->leaveType->name ?? null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total_days' => $this->total_days,
        ];
    }

    public function workflowModuleKey(): string
    {
        return 'leave_request';
    }
}
