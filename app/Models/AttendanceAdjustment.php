<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Traits\HasWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAdjustment extends Model implements Approvable
{
    use HasFactory, HasWorkflow;

    protected $fillable = [
        'employee_id', 'attendance_id', 'adjustment_date', 'requested_check_in',
        'requested_check_out', 'reason', 'approval_status', 'status'
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'status' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function approvalPayload(): array
    {
        return [
            'employee' => $this->employee->full_name ?? null,
            'adjustment_date' => $this->adjustment_date,
            'requested_check_in' => $this->requested_check_in,
            'requested_check_out' => $this->requested_check_out,
        ];
    }

    public function workflowModuleKey(): string
    {
        return 'attendance_adjustment';
    }
}
