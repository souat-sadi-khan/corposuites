<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'year', 'allocated_days', 'used_days', 'status'
    ];

    protected $casts = [
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'status' => 'boolean',
    ];

    protected $appends = ['remaining_days'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingDaysAttribute()
    {
        return round($this->allocated_days - $this->used_days, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
