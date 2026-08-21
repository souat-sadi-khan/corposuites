<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_request_id', 'attendance_date', 'check_in', 'check_out', 'check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude', 'check_in_source', 'check_out_source', 'attendance_status', 'remarks',
        'leave_original_status', 'leave_original_remarks', 'status'
    ];

    protected $casts = [
        'attendance_date' => 'date',
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
}
