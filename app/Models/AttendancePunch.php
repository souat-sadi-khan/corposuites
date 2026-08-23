<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One individual check-in/check-out EVENT — the detailed session log
 * underneath a day's single `Attendance` summary row (see this table's own
 * migration doc comment for why the two are kept separate). Never queried
 * by the existing Monthly Sheet/Attendance Report/export pipeline for its
 * own aggregate numbers (those keep reading Attendance's own stored
 * check_in/check_out/worked_minutes) — this is purely the per-session
 * detail (source, notes, exact punched_at) surfaced on top of that summary.
 */
class AttendancePunch extends Model
{
    protected $fillable = [
        'employee_id', 'attendance_id', 'attendance_date', 'punch_type',
        'punched_at', 'latitude', 'longitude', 'source', 'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'punched_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Human-readable source label — same vocabulary the check-in/out source
     * enum already validates against (browser_geolocation/fingerprint/
     * face/id_card), kept here as the single place that maps a raw source
     * value to display text so it's never spelled out twice.
     */
    public const SOURCE_LABELS = [
        'browser_geolocation' => 'Web (GPS)',
        'fingerprint' => 'Fingerprint Device',
        'face' => 'Face Recognition',
        'id_card' => 'ID Card Punch',
    ];

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? ($this->source ?: 'Unknown');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * The single most recent punch for this employee across BOTH the
     * reference date and the day before it — covers an overnight shift
     * whose session is still open (a check-in yesterday 22:00 with no
     * check-out yet is still what governs today's check-in/out rules).
     * Whichever punch actually happened last, on whichever calendar day, is
     * what decides whether a session is currently open — shared here (not
     * duplicated) so AttendancePortalController's own check-in/out gating
     * and AttendanceStatusService's "can I check in right now" widget
     * resolution can never disagree about the answer.
     */
    public static function latestFor(int $employeeId, Carbon $referenceDate): ?self
    {
        return self::where('employee_id', $employeeId)
            ->whereIn('attendance_date', [$referenceDate->toDateString(), $referenceDate->copy()->subDay()->toDateString()])
            ->orderByDesc('punched_at')
            ->first();
    }
}
