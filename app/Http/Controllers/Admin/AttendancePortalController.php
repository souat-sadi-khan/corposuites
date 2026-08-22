<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Attendance; use App\Models\AttendanceAdjustment; use App\Models\Employee; use App\Models\Holiday; use App\Services\AttendanceAdjustmentService; use App\Services\AttendanceReportService; use Carbon\Carbon; use Illuminate\Http\Request;
class AttendancePortalController extends Controller {
    public function __construct(private AttendanceReportService $reportService)
    {
    }

    /**
     * "My Attendance" — always the logged-in admin's OWN employee record,
     * never anyone else's (see the employee() resolver below). Accepts
     * EITHER a `month` (Y-m) OR an explicit `date_from`/`date_to` range;
     * defaults to the current month when neither is given.
     */
    public function portal(Request $request)
    {
        $employee = $this->employee();
        $attendance = Attendance::where('employee_id', $employee->id)->whereDate('attendance_date', today())->first();

        [$from, $to, $month] = $this->resolveRange($request);
        $report = $this->reportService->build($employee, $from, $to);

        // One query for every adjustment request touching this range, keyed
        // by date, so each table row can show its own indicator (Pending/
        // Approved/Rejected) without a query per day.
        $adjustments = AttendanceAdjustment::where('employee_id', $employee->id)
            ->whereBetween('adjustment_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($row) => $row->adjustment_date->toDateString());

        return view('admin.attendance-portal.index', compact('employee', 'attendance', 'report', 'from', 'to', 'month', 'adjustments'));
    }

    /**
     * Self-service "Request Adjustment" form for ONE of the employee's own
     * past days — reuses the existing AttendanceAdjustment model/service (no
     * duplicate correction system), just with a smaller, employee-facing
     * input surface than the admin create form (no employee_id/status
     * fields to fill in — those are forced server-side, never trusted from
     * the request).
     */
    public function adjustmentForm(Request $request)
    {
        $employee = $this->employee();
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : today();
        abort_if($date->isAfter(today()), 422, 'You cannot request an adjustment for a future date.');

        $attendance = Attendance::where('employee_id', $employee->id)->whereDate('attendance_date', $date)->first();
        $pendingExists = AttendanceAdjustment::where('employee_id', $employee->id)
            ->whereDate('adjustment_date', $date)
            ->where('approval_status', 'pending')
            ->exists();

        return view('admin.attendance-portal.adjustment', compact('employee', 'date', 'attendance', 'pendingExists'));
    }

    public function storeAdjustment(Request $request, AttendanceAdjustmentService $service)
    {
        $employee = $this->employee();

        $data = $request->validate([
            'adjustment_date' => 'required|date|before_or_equal:today',
            'requested_check_in' => 'nullable|date_format:H:i',
            'requested_check_out' => 'nullable|date_format:H:i|after:requested_check_in',
            'reason' => 'required|string|max:1000',
        ]);

        $alreadyPending = AttendanceAdjustment::where('employee_id', $employee->id)
            ->whereDate('adjustment_date', $data['adjustment_date'])
            ->where('approval_status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return response()->json(['status' => false, 'message' => 'You already have a pending adjustment request for this date.'], 422);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $data['adjustment_date'])
            ->first();

        $service->create([
            'employee_id' => $employee->id, // never trusted from the request — always the logged-in admin's own linked employee
            'attendance_id' => $attendance?->id,
            'adjustment_date' => $data['adjustment_date'],
            'requested_check_in' => $data['requested_check_in'] ?? null,
            'requested_check_out' => $data['requested_check_out'] ?? null,
            'reason' => $data['reason'],
            'approval_status' => 'pending',
            'status' => true,
        ]);

        return response()->json(['status' => true, 'message' => 'Adjustment request submitted successfully.']);
    }

    /**
     * Shared by portal() and, if a range spans more than one calendar month,
     * also drives which "Month" dropdown value (if any) is pre-selected in
     * the filter form.
     */
    private function resolveRange(Request $request): array
    {
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $from = Carbon::parse($request->input('date_from'))->startOfDay();
            $to = Carbon::parse($request->input('date_to'))->endOfDay();
            if ($to->lt($from)) {
                [$from, $to] = [$to, $from];
            }
            // Cap at 3 months so a mistyped/abusive range can't force the
            // report to compute over years of days in one request.
            if ($from->diffInDays($to) > 92) {
                $to = $from->copy()->addDays(92);
            }

            return [$from, $to, null];
        }

        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01');

        return [$start->copy()->startOfMonth(), $start->copy()->endOfMonth(), $month];
    }

    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'source' => 'nullable|in:browser_geolocation,fingerprint,face,id_card',
        ]);

        $geofenceError = $this->geofenceError($data['latitude'], $data['longitude']);
        if ($geofenceError) {
            return response()->json(['status' => false, 'message' => $geofenceError], 422);
        }

        $employee = $this->employee();
        $today = today()->toDateString();
        $attendance = Attendance::firstOrNew(['employee_id' => $employee->id, 'attendance_date' => $today]);

        if ($attendance->exists && $attendance->check_in) {
            return response()->json(['status' => false, 'message' => 'You have already checked in today.'], 422);
        }

        $late = $this->isLate(now(), $employee);
        $attendance->fill([
            'check_in' => now()->format('H:i:s'),
            'check_in_latitude' => $data['latitude'],
            'check_in_longitude' => $data['longitude'],
            'check_in_source' => $data['source'] ?? 'browser_geolocation',
            'attendance_status' => $late ? 'late' : 'present',
            'status' => true,
        ]);
        $attendance->save();

        return response()->json(['status' => true, 'message' => $late ? 'Checked in late.' : 'Checked in successfully.']);
    }

    public function checkOut(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'source' => 'nullable|in:browser_geolocation,fingerprint,face,id_card',
        ]);

        $geofenceError = $this->geofenceError($data['latitude'], $data['longitude']);
        if ($geofenceError) {
            return response()->json(['status' => false, 'message' => $geofenceError], 422);
        }

        $employee = $this->employee();

        // Prefer today's own record (the normal case). If there isn't one,
        // fall back to an still-open punch from yesterday — an overnight
        // shift (e.g. check-in 22:00, check-out 06:00) checks out on the
        // calendar day AFTER its attendance_date, so looking up by today()
        // alone would never find it and would wrongly report "check in
        // before checking out". Only an OPEN yesterday record qualifies for
        // this fallback, so a normal employee who simply hasn't checked in
        // yet today doesn't get matched against yesterday's already-closed
        // punch and told they've "already checked out".
        $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first()
            ?? Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today()->subDay())
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->first();

        if (!$attendance || !$attendance->check_in) {
            return response()->json(['status' => false, 'message' => 'Check in before checking out.'], 422);
        }

        if ($attendance->check_out) {
            return response()->json(['status' => false, 'message' => 'You have already checked out today.'], 422);
        }

        $checkOutTime = now();

        $update = [
            'check_out' => $checkOutTime->format('H:i:s'),
            'check_out_latitude' => $data['latitude'],
            'check_out_longitude' => $data['longitude'],
            'check_out_source' => $data['source'] ?? 'browser_geolocation',
        ];

        $checkoutStatus = $this->checkoutStatus($employee, $attendance->check_in, $checkOutTime, $attendance->attendance_status);
        if ($checkoutStatus) {
            $update['attendance_status'] = $checkoutStatus;
        }

        $attendance->update($update);

        return response()->json(['status' => true, 'message' => 'Checked out successfully.']);
    }

    /**
     * Called directly by an external biometric/fingerprint/face device — see
     * the "Attendance Device Token" field + guide on the HRM Settings page.
     * No login/browser session is involved; the device authenticates itself
     * with the X-Attendance-Token header instead (checked below). This
     * route is deliberately registered OUTSIDE the isAdmin-protected route
     * group and exempted from CSRF (see routes/admin.php + bootstrap/app.php)
     * so a real device can reach it.
     */
    public function devicePunch(Request $request)
    {
        $token = (string) get_settings('hrm_attendance_device_token');
        if (!$token || !hash_equals($token, (string) $request->header('X-Attendance-Token'))) {
            return response()->json(['status' => false, 'message' => 'Invalid or missing attendance device token.'], 403);
        }

        $data = $request->validate([
            'employee_code' => 'required|string',
            'event' => 'required|in:check_in,check_out',
            'occurred_at' => 'required|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'source' => 'required|in:fingerprint,face,id_card',
        ]);

        $employee = Employee::where('employee_code', $data['employee_code'])->first();
        if (!$employee) {
            return response()->json(['status' => false, 'message' => "No employee found with code '{$data['employee_code']}'."], 404);
        }

        $when = Carbon::parse($data['occurred_at']);
        $attendance = Attendance::firstOrNew(['employee_id' => $employee->id, 'attendance_date' => $when->toDateString()]);
        $prefix = $data['event'] === 'check_in' ? 'check_in' : 'check_out';

        if ($attendance->$prefix) {
            return response()->json(['status' => true, 'message' => 'Punch already recorded.']);
        }

        $values = [
            $prefix => $when->format('H:i:s'),
            $prefix . '_latitude' => $data['latitude'] ?? null,
            $prefix . '_longitude' => $data['longitude'] ?? null,
            $prefix . '_source' => $data['source'],
            'status' => true,
        ];

        if ($data['event'] === 'check_in') {
            $values['attendance_status'] = $this->isLate($when, $employee) ? 'late' : 'present';
        } else {
            $checkoutStatus = $this->checkoutStatus($employee, $attendance->check_in, $when, $attendance->attendance_status);
            if ($checkoutStatus) {
                $values['attendance_status'] = $checkoutStatus;
            }
        }

        $attendance->fill($values)->save();

        return response()->json(['status' => true, 'message' => 'Punch recorded successfully.']);
    }

    /**
     * Admin-facing monthly attendance SHEET — one row per employee, one
     * column per day of the month (Employee | Department | 1 2 3 ... 31 |
     * Summary), not the single-employee table this used to be. Accepts an
     * arbitrary set of employees (department/employee filters), unlike the
     * self-service portal() above which is always locked to the logged-in
     * admin's own employee.
     */
    public function monthly(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01');
        $from = $start->copy()->startOfMonth();
        $to = $start->copy()->endOfMonth();

        $departments = \App\Models\Department::active()->orderBy('name')->get();

        $employeesQuery = Employee::active()->with('department')->orderBy('first_name');
        if ($request->filled('department_id')) {
            $employeesQuery->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('employee_id')) {
            $employeesQuery->where('id', $request->input('employee_id'));
        }
        $employees = $employeesQuery->get();

        $allEmployeesForFilter = Employee::active()->orderBy('first_name')->get();
        $reports = $employees->isNotEmpty() ? $this->reportService->buildForEmployees($employees, $from, $to) : [];

        // The header row needs to know which day columns are a weekend
        // per THIS app's own configurable weekend-days setting — Carbon's
        // built-in isWeekend() always means Saturday/Sunday regardless of
        // what's actually configured, which would disagree with every data
        // row (already correctly computed by AttendanceReportService).
        $weekendDays = collect(explode(',', (string) get_settings('leave_weekend_days', '5,6')))
            ->filter(fn ($d) => $d !== '')
            ->map(fn ($d) => (int) $d)
            ->all();

        return view('admin.attendances.monthly', compact('month', 'from', 'to', 'employees', 'departments', 'allEmployeesForFilter', 'reports', 'weekendDays'));
    }
 private function employee(): Employee { $employee=auth()->guard('admin')->user()?->employee; abort_unless($employee,403,'This account is not linked to an employee.'); return $employee; }

    /**
     * Late is judged against the EMPLOYEE'S OWN assigned shift start time
     * (Shifts module), not a single global cutoff — different shifts start
     * at different times, so one flat clock-time for every employee was
     * wrong. Falls back to the HRM Settings "Default Shift" only for
     * employees with no shift assigned at all.
     */
    private function isLate(Carbon $time, Employee $employee): bool
    {
        $shiftStart = $employee->shift?->start_time ?? get_settings('hrm_default_shift_start', '09:00:00');
        $graceMinutes = (int) get_settings('hrm_late_grace_minutes', 15);

        $cutoff = Carbon::parse($time->toDateString() . ' ' . $shiftStart)->addMinutes($graceMinutes);

        return $time->gt($cutoff);
    }

    /**
     * Whether a check-out should be recorded as a half day or an early
     * leave. The threshold is a PERCENTAGE of the employee's own shift
     * duration (Shifts module), not a flat number of hours — a flat
     * threshold would misjudge a part-time 4-hour shift and a full 8-hour
     * shift the same way. Falls back to the HRM Settings "Default Shift"
     * window (start/end) only for employees with no shift assigned.
     * Handles overnight shifts (end time earlier than start time) by
     * treating the shift as crossing midnight.
     *
     * Worked hours below the half-day threshold => 'half_day'. Worked
     * hours at or above that threshold but still short of the full shift
     * => 'early_leave' — a distinct, lighter-weight signal than half_day
     * (most of the shift was worked, just not checked out on time).
     * Worked hours meeting or exceeding the full shift duration leave the
     * status untouched (still 'present'/'late' from check-in).
     *
     * Returns null when neither rule applies (no check-in yet, already on
     * leave/absent, or the full shift was worked) so the caller can leave
     * the existing attendance_status untouched.
     */
    private function checkoutStatus(Employee $employee, ?string $checkIn, Carbon $checkOutTime, ?string $currentStatus): ?string
    {
        if (!$checkIn || in_array($currentStatus, ['on_leave', 'absent'], true)) {
            return null;
        }

        $shiftStart = $employee->shift?->start_time ?? get_settings('hrm_default_shift_start', '09:00:00');
        $shiftEnd = $employee->shift?->end_time ?? get_settings('hrm_default_shift_end', '18:00:00');

        $shiftStartTime = Carbon::parse($shiftStart);
        $shiftEndTime = Carbon::parse($shiftEnd);
        if ($shiftEndTime->lte($shiftStartTime)) {
            $shiftEndTime->addDay(); // overnight shift, e.g. 22:00 - 06:00
        }

        $shiftDurationHours = $shiftStartTime->diffInMinutes($shiftEndTime) / 60;
        if ($shiftDurationHours <= 0) {
            return null;
        }

        $checkInTime = Carbon::parse($checkOutTime->toDateString() . ' ' . $checkIn);
        if ($checkInTime->gt($checkOutTime)) {
            // Check-out landed on the calendar day after check-in (an
            // overnight shift, e.g. checked in 22:00, checked out 05:00) —
            // the check-in instant is actually the day before checkout.
            $checkInTime->subDay();
        }

        $workedHours = $checkInTime->diffInMinutes($checkOutTime) / 60;

        $thresholdPercent = (float) get_settings('hrm_half_day_threshold_percent', 50);
        $thresholdHours = $shiftDurationHours * ($thresholdPercent / 100);

        if ($workedHours < $thresholdHours) {
            return 'half_day';
        }

        return $workedHours < $shiftDurationHours ? 'early_leave' : null;
    }

    /**
     * Returns a human-readable rejection message if geofencing is enabled
     * and the given coordinates fall outside the configured office radius —
     * or null when the punch should be allowed (geofencing off, or no office
     * location has been configured yet, so nothing to check against).
     *
     * Only applied to browser/mobile check-in/out (checkIn/checkOut) — a
     * physical device punch (devicePunch) is a fixed installation at the
     * office already, so its own GPS coordinates (if it even sends any)
     * aren't a meaningful thing to geofence.
     */
    private function geofenceError(float $latitude, float $longitude): ?string
    {
        if (!get_settings('hrm_geofence_required')) {
            return null;
        }

        $officeLat = get_settings('hrm_office_latitude');
        $officeLng = get_settings('hrm_office_longitude');
        if ($officeLat === null || $officeLng === null || $officeLat === '' || $officeLng === '') {
            return null; // geofencing is on but no office location is set yet — don't block everyone
        }

        $radius = (float) get_settings('hrm_geofence_radius_meters', 200);
        $distance = $this->distanceInMeters((float) $officeLat, (float) $officeLng, $latitude, $longitude);

        if ($distance > $radius) {
            $distanceLabel = $distance >= 1000
                ? round($distance / 1000, 1) . 'km'
                : round($distance) . 'm';

            return "You're too far from the office to check in/out ({$distanceLabel} away, allowed within " . round($radius) . 'm).';
        }

        return null;
    }

    /**
     * Straight-line distance in meters between two GPS coordinates
     * (Haversine formula). Good enough for a "are you near the office"
     * check — no external geocoding service or package required.
     */
    public static function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
