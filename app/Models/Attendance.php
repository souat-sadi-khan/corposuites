<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_request_id', 'attendance_date', 'check_in', 'check_out', 'check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude', 'check_in_source', 'check_out_source', 'attendance_status', 'overtime_hours', 'worked_minutes', 'remarks',
        'leave_original_status', 'leave_original_remarks', 'status'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'overtime_hours' => 'decimal:2',
        'worked_minutes' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Same source-label vocabulary AttendancePunch::SOURCE_LABELS uses —
     * check_in_source/check_out_source here represent the FIRST check-in's
     * and LAST check-out's source respectively (this row can span multiple
     * punch sessions in a day; see AttendancePunch's own doc comment).
     */
    public function getCheckInSourceLabelAttribute(): ?string
    {
        return $this->check_in_source ? (AttendancePunch::SOURCE_LABELS[$this->check_in_source] ?? $this->check_in_source) : null;
    }

    public function getCheckOutSourceLabelAttribute(): ?string
    {
        return $this->check_out_source ? (AttendancePunch::SOURCE_LABELS[$this->check_out_source] ?? $this->check_out_source) : null;
    }

    /**
     * Every individual check-in/check-out session recorded for this day,
     * oldest first — the detailed punch log underneath this row's own
     * first-in/last-out/worked_minutes summary.
     */
    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class)->orderBy('punched_at');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The approved LeaveRequest that produced this row's 'on_leave' status
     * (LeaveAttendanceService::syncApprovedLeave() only ever sets
     * leave_request_id from an approved request) — lets the Monthly Sheet
     * and admin Report show the actual leave type/duration instead of just
     * a bare "On Leave" bucket.
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * Every adjustment request ever made by this row's EMPLOYEE — not
     * narrowed to this specific attendance_date, since Eloquent can't match
     * a second column during eager loading. The admin Attendance list
     * (AttendanceController::index()) eager-loads this once per page (one
     * query for every distinct employee_id actually shown, never N+1) and
     * then picks out the one matching this row's own date in memory, so the
     * per-row "Adjustment" indicator costs nothing extra per row.
     */
    public function employeeAdjustments(): HasMany
    {
        return $this->hasMany(AttendanceAdjustment::class, 'employee_id', 'employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
