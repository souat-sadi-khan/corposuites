<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Attendance; use App\Models\AttendanceAdjustment; use App\Models\AttendancePunch; use App\Models\Employee; use App\Models\Holiday; use App\Services\AttendanceAdjustmentService; use App\Services\AttendanceReportService; use App\Services\AttendanceStatusService; use App\Traits\ExportsCsv; use App\Traits\ExportsHtmlSpreadsheet; use Carbon\Carbon; use Illuminate\Http\Request;
class AttendancePortalController extends Controller {
    use ExportsHtmlSpreadsheet, ExportsCsv;

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
        // Single source of truth for "can I check in/out right now" and
        // today's session breakdown — the exact same resolver the header
        // widget uses, so the two can never disagree with each other or
        // with what the check-in/check-out endpoints will actually accept.
        $todayStatus = AttendanceStatusService::forEmployee($employee);

        [$from, $to, $month] = $this->resolveRange($request);
        $report = $this->reportService->build($employee, $from, $to);

        // One query for every adjustment request touching this range, keyed
        // by date, so each table row can show its own indicator (Pending/
        // Approved/Rejected) without a query per day.
        $adjustments = AttendanceAdjustment::where('employee_id', $employee->id)
            ->whereBetween('adjustment_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($row) => $row->adjustment_date->toDateString());

        return view('admin.attendance-portal.index', compact('employee', 'attendance', 'report', 'from', 'to', 'month', 'adjustments', 'todayStatus'));
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
    /**
     * Delegates to AttendanceReportService::resolveRange() — moved there so
     * the admin Attendance Report (and later exports) share the exact same
     * "Month OR explicit Date From/Date To" parsing instead of a second copy
     * of this logic. Kept as a private wrapper here purely so every existing
     * call site in this controller doesn't need to change.
     */
    private function resolveRange(Request $request): array
    {
        return $this->reportService->resolveRange($request);
    }

    /**
     * Multiple check-in/check-out CYCLES per day are allowed (lunch break, a
     * trip out and back, etc.) — the only thing ever rejected is a check-in
     * while a session is still open. See AttendancePunch's own migration doc
     * comment for the full design: this row's own check_in/attendance_status
     * still reflect the day's FIRST session (late-detection is judged once,
     * at the start of the day), check_out/its source reflect the LATEST
     * session, and worked_minutes accumulates across every closed session.
     */
    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'source' => 'nullable|in:browser_geolocation,fingerprint,face,id_card',
            'notes' => 'nullable|string|max:1000',
        ]);

        $geofenceError = $this->geofenceError($data['latitude'], $data['longitude']);
        if ($geofenceError) {
            return response()->json(['status' => false, 'message' => $geofenceError], 422);
        }

        $result = $this->performCheckIn($this->employee(), now(), [
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'source' => $data['source'] ?? 'browser_geolocation',
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['status' => $result['status'], 'message' => $result['message']], $result['status'] ? 200 : 422);
    }

    public function checkOut(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'source' => 'nullable|in:browser_geolocation,fingerprint,face,id_card',
            'notes' => 'nullable|string|max:1000',
        ]);

        $geofenceError = $this->geofenceError($data['latitude'], $data['longitude']);
        if ($geofenceError) {
            return response()->json(['status' => false, 'message' => $geofenceError], 422);
        }

        $result = $this->performCheckOut($this->employee(), now(), [
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'source' => $data['source'] ?? 'browser_geolocation',
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['status' => $result['status'], 'message' => $result['message']], $result['status'] ? 200 : 422);
    }

    /**
     * Called directly by an external biometric/fingerprint/face/card device
     * — see the "Attendance Device Token" field + guide on the HRM Settings
     * page. No login/browser session is involved; the device authenticates
     * itself with the X-Attendance-Token header instead (checked below).
     * This route is deliberately registered OUTSIDE the isAdmin-protected
     * route group and exempted from CSRF (see routes/admin.php +
     * bootstrap/app.php) so a real device can reach it.
     *
     * A device can punch multiple sessions a day too (an employee tapping
     * out for lunch and back in again is completely normal on a real
     * card/fingerprint reader) — same performCheckIn()/performCheckOut()
     * used by the interactive endpoints above. The one deliberate
     * difference: a rejected punch here (e.g. a duplicate signal from a
     * flaky reader re-sending the same tap) is reported back as a harmless
     * "already recorded" success rather than an error — a physical device
     * can't show a person a rejection dialog the way a web button can, so
     * this preserves the original devicePunch() behavior of silently
     * absorbing a redundant signal instead of surfacing it as a failure.
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
            'notes' => 'nullable|string|max:1000',
        ]);

        $employee = Employee::where('employee_code', $data['employee_code'])->first();
        if (!$employee) {
            return response()->json(['status' => false, 'message' => "No employee found with code '{$data['employee_code']}'."], 404);
        }

        $when = Carbon::parse($data['occurred_at']);
        $meta = [
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'source' => $data['source'],
            'notes' => $data['notes'] ?? null,
        ];

        $result = $data['event'] === 'check_in'
            ? $this->performCheckIn($employee, $when, $meta)
            : $this->performCheckOut($employee, $when, $meta);

        if (!$result['status']) {
            return response()->json(['status' => true, 'message' => 'Punch already recorded.']);
        }

        return response()->json(['status' => true, 'message' => 'Punch recorded successfully.']);
    }

    /**
     * @return array{status: bool, message: string, attendance?: Attendance, punch?: AttendancePunch}
     */
    private function performCheckIn(Employee $employee, Carbon $when, array $meta): array
    {
        $latest = AttendancePunch::latestFor($employee->id, $when);
        if ($latest && $latest->punch_type === 'check_in') {
            return ['status' => false, 'message' => 'You are already checked in — check out before checking in again.'];
        }

        $dateKey = $when->toDateString();
        $attendance = Attendance::firstOrNew(['employee_id' => $employee->id, 'attendance_date' => $dateKey]);

        // Late-detection and the row's own check_in/attendance_status only
        // ever reflect the FIRST session of the day — a later session (after
        // an earlier check-out) doesn't re-judge lateness or overwrite them.
        $isFirstSessionToday = !$attendance->exists || !$attendance->check_in;

        if ($isFirstSessionToday) {
            $late = $this->isLate($when, $employee);
            $attendance->fill([
                'check_in' => $when->format('H:i:s'),
                'check_in_latitude' => $meta['latitude'] ?? null,
                'check_in_longitude' => $meta['longitude'] ?? null,
                'check_in_source' => $meta['source'],
                'attendance_status' => $late ? 'late' : 'present',
                'status' => true,
            ]);
        }
        $attendance->save();

        $punch = AttendancePunch::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'attendance_date' => $dateKey,
            'punch_type' => 'check_in',
            'punched_at' => $when,
            'latitude' => $meta['latitude'] ?? null,
            'longitude' => $meta['longitude'] ?? null,
            'source' => $meta['source'],
            'notes' => $meta['notes'] ?? null,
        ]);

        $message = $isFirstSessionToday
            ? ($attendance->attendance_status === 'late' ? 'Checked in late.' : 'Checked in successfully.')
            : 'Checked back in successfully.';

        return ['status' => true, 'message' => $message, 'attendance' => $attendance, 'punch' => $punch];
    }

    /**
     * @return array{status: bool, message: string, attendance?: Attendance, punch?: AttendancePunch}
     */
    private function performCheckOut(Employee $employee, Carbon $when, array $meta): array
    {
        $latest = AttendancePunch::latestFor($employee->id, $when);
        if (!$latest || $latest->punch_type !== 'check_in') {
            return ['status' => false, 'message' => 'Check in before checking out.'];
        }

        // The open punch already knows exactly which day's Attendance row it
        // belongs to — no more separate "is this an overnight shift, look at
        // yesterday's row instead" guesswork, since that link was recorded
        // the moment the session was opened.
        $attendance = $latest->attendance_id ? Attendance::find($latest->attendance_id) : null;
        if (!$attendance) {
            return ['status' => false, 'message' => 'Check in before checking out.'];
        }

        $sessionMinutes = max(0, (int) round(abs($when->diffInSeconds($latest->punched_at)) / 60));
        $attendance->worked_minutes = (int) $attendance->worked_minutes + $sessionMinutes;
        $attendance->check_out = $when->format('H:i:s');
        $attendance->check_out_latitude = $meta['latitude'] ?? null;
        $attendance->check_out_longitude = $meta['longitude'] ?? null;
        $attendance->check_out_source = $meta['source'];

        $checkoutStatus = $this->checkoutStatus($employee, $attendance, $attendance->worked_minutes);
        if ($checkoutStatus) {
            $attendance->attendance_status = $checkoutStatus;
        }
        $attendance->save();

        $punch = AttendancePunch::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'attendance_date' => $attendance->attendance_date->toDateString(),
            'punch_type' => 'check_out',
            'punched_at' => $when,
            'latitude' => $meta['latitude'] ?? null,
            'longitude' => $meta['longitude'] ?? null,
            'source' => $meta['source'],
            'notes' => $meta['notes'] ?? null,
        ]);

        return ['status' => true, 'message' => 'Checked out successfully.', 'attendance' => $attendance, 'punch' => $punch];
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
        return view('admin.attendances.monthly', $this->buildMonthlyData($request));
    }

    /**
     * PDF export of the Monthly Attendance Sheet — same "reuse the existing
     * <x-print-document> shell instead of a server-side PDF library"
     * convention already established for the Attendance Report's own PDF
     * export (Module 9), and runs through the EXACT SAME buildMonthlyData()
     * as the browser view, so the export can never drift from — or silently
     * ignore — whatever filters (department/designation/shift/employee
     * type/employment status/employee, or the late/missing-checkout/
     * overtime "only" toggles) are currently applied on the sheet.
     */
    public function monthlyExportPdf(Request $request)
    {
        $data = $this->buildMonthlyData($request);
        $data['filterSummary'] = $this->reportService->filterSummary($request, $data['filters']);

        return view('admin.attendances.monthly-pdf', $data);
    }

    /**
     * "Excel" export of the Monthly Attendance Sheet — see
     * App\Traits\ExportsHtmlSpreadsheet's own doc comment for why this is a
     * styled HTML table served as .xls (no maatwebsite/excel or any other
     * new dependency) rather than the plain-values .csv-tagged-as-excel
     * convention HrmDetailExportController already has. Same
     * buildMonthlyData() pipeline as the browser view and the PDF export.
     */
    public function monthlyExportExcel(Request $request)
    {
        $data = $this->buildMonthlyData($request);
        $data['filterSummary'] = $this->reportService->filterSummary($request, $data['filters']);

        return $this->htmlSpreadsheetResponse('admin.attendances.monthly-excel', $data, 'monthly_attendance_sheet');
    }

    /**
     * PART 11's "clean machine-readable CSV" for the Monthly Sheet.
     * Deliberately NOT the same wide one-column-per-day grid the on-screen
     * sheet/PDF/Excel exports all share — a grid's day columns are named
     * "01".."31" and their COUNT changes every month (28-31), which fails
     * PART 11's own "use stable column names" CSV requirement outright (a
     * consumer reading this month's file wouldn't get the same header row
     * next month). Instead this is one row per EMPLOYEE PER DAY — a fixed,
     * never-changing column set regardless of month length, and a shape
     * that's actually easier for payroll/BI tooling to import than a wide
     * grid would be. Same buildMonthlyData() pipeline as every other
     * output format.
     */
    public function monthlyExportCsv(Request $request)
    {
        $data = $this->buildMonthlyData($request);
        $employees = $data['employees'];
        $reports = $data['reports'];
        $adjustments = $data['adjustments'];

        $headers = [
            'Employee Code', 'Employee Name', 'Department', 'Designation',
            'Date', 'Day', 'Status', 'Check In', 'Check Out',
            'Check In Source', 'Sessions',
            'Worked Hours', 'Overtime Hours', 'Leave Type', 'Leave Duration',
            'Adjustment Status', 'Remarks',
        ];

        $rows = (function () use ($employees, $reports, $adjustments) {
            foreach ($employees as $employee) {
                $report = $reports[$employee->id] ?? null;
                if (!$report) {
                    continue;
                }

                foreach ($report['days'] as $day) {
                    $record = $day['record'];
                    $adjustment = $adjustments->get($employee->id . '|' . $day['date']->toDateString());

                    yield [
                        $employee->employee_code,
                        $employee->full_name,
                        $employee->department?->name ?? '',
                        $employee->designation?->name ?? '',
                        $day['date']->format('Y-m-d'),
                        $day['date']->format('l'),
                        ucwords(str_replace('_', ' ', $day['bucket'])),
                        $record?->check_in ? Carbon::parse($record->check_in)->format('h:i A') : '',
                        $record?->check_out ? Carbon::parse($record->check_out)->format('h:i A') : '',
                        $record?->check_in_source_label ?? '',
                        $record && $record->punches->count() > 2 ? intdiv($record->punches->count(), 2) : ($record?->check_in ? 1 : ''),
                        $day['worked_label'] === '--' ? '' : $day['worked_label'],
                        $record?->overtime_hours > 0 ? $record->overtime_hours : '',
                        $day['leave_type'] ?? '',
                        $day['leave_type'] ? $day['leave_duration_label'] : '',
                        $adjustment ? ucfirst($adjustment->approval_status) : '',
                        $record?->remarks ?? '',
                    ];
                }
            }
        })();

        return $this->csvResponse('monthly_attendance_sheet', $headers, $rows);
    }

    /**
     * The one shared pipeline both the on-screen sheet and its PDF export
     * read from (mirrors AttendanceReportController::buildReportData()'s own
     * "single source of truth for every output format" shape).
     */
    private function buildMonthlyData(Request $request): array
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01');
        $from = $start->copy()->startOfMonth();
        $to = $start->copy()->endOfMonth();

        // Same advanced-search filter set (department/designation/shift/
        // employee type/employment status/employee + late/missing-checkout/
        // overtime "only" toggles) as the admin Attendance Report — shared
        // via AttendanceReportService so the two screens can never disagree
        // about what a given filter combination matches.
        $employees = $this->reportService->filteredEmployeesQuery($request)->get();
        $reports = $employees->isNotEmpty() ? $this->reportService->buildForEmployees($employees, $from, $to) : [];
        [$employees, $reports] = $this->reportService->narrowToActivityFilters($employees, $reports, $request);

        $filters = $this->reportService->filterOptions();

        // PART 9 integration: one query for every adjustment request made by
        // ANY of the currently-shown employees across the whole visible
        // month, keyed by "employeeId|Y-m-d" — so each day cell can look up
        // its own adjustment status/eligibility with zero extra queries per
        // cell (mirrors the exact "keyBy date" batch-fetch portal() already
        // uses for a single employee, just extended to many).
        $adjustments = $employees->isNotEmpty()
            ? AttendanceAdjustment::whereIn('employee_id', $employees->pluck('id'))
                ->whereBetween('adjustment_date', [$from->toDateString(), $to->toDateString()])
                ->get()
                ->keyBy(fn ($row) => $row->employee_id . '|' . $row->adjustment_date->toDateString())
            : collect();

        // The header row needs to know which day columns are a weekend —
        // via the shared WeekendCalendarService (called directly from the
        // 3 monthly-sheet views per date), NOT Carbon's own built-in
        // isWeekend() (always Sat/Sun) or a bare day-of-week array, since
        // this app's weekend calculation can now also be date-parity-based
        // (even/odd calendar dates), which has no fixed day-of-week set at
        // all. Nothing to precompute here anymore — kept as a comment
        // pointer since this is a common place a future edit might look.

        return compact('month', 'from', 'to', 'employees', 'filters', 'reports', 'adjustments');
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
     *
     * Takes the day's TOTAL worked minutes so far (summed across every
     * closed session, not just the one that just ended) — with multiple
     * check-in/out cycles now allowed per day, judging half-day/early-leave
     * against only the most recent session's own span would wrongly flag a
     * lunch-break-shortened final session as a half day even when the
     * day's cumulative hours were actually complete.
     *
     * Worked hours below the half-day threshold => 'half_day'. Worked
     * hours at or above that threshold but still short of the full shift
     * => 'early_leave' — a distinct, lighter-weight signal than half_day
     * (most of the shift was worked, just not quite enough to call it
     * complete). Worked hours meeting or exceeding the full shift duration
     * revert back to whichever of 'present'/'late' the FIRST check-in of
     * the day determined — re-derived fresh from $attendance's own stored
     * check_in time (never from whatever attendance_status currently holds)
     * specifically because an EARLIER, still-partial checkout in the same
     * day may have already (correctly, at the time) set it to half_day/
     * early_leave; a later session bringing the day's total up to a full
     * shift needs to be able to genuinely REVERT that, not just leave a
     * now-stale classification sitting there.
     *
     * Returns null only when neither rule applies AND there's nothing to
     * revert to — i.e. already on leave/absent, or no usable shift window —
     * so the caller can leave the existing attendance_status untouched in
     * those specific cases.
     */
    private function checkoutStatus(Employee $employee, Attendance $attendance, int $totalWorkedMinutes): ?string
    {
        if (in_array($attendance->attendance_status, ['on_leave', 'absent'], true)) {
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

        $workedHours = $totalWorkedMinutes / 60;

        $thresholdPercent = (float) get_settings('hrm_half_day_threshold_percent', 50);
        $thresholdHours = $shiftDurationHours * ($thresholdPercent / 100);

        if ($workedHours < $thresholdHours) {
            return 'half_day';
        }

        if ($workedHours < $shiftDurationHours) {
            return 'early_leave';
        }

        if (!$attendance->check_in) {
            return null;
        }

        $checkInMoment = Carbon::parse($attendance->attendance_date->toDateString() . ' ' . $attendance->check_in);

        return $this->isLate($checkInMoment, $employee) ? 'late' : 'present';
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
