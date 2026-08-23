<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    use ActivityLogger;

    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Attendance::query()->with(['employee', 'employeeAdjustments']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('attendance_status', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('attendance_date', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.attendances.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    $avatar = Images::show($row->employee->photo);

                    return '
                        <div class="d-flex align-items-center">
                            <div class="mr-2 employee-avatar">
                                ' . $avatar . '
                            </div>
                            <div>
                                <b class="tl-name-txt">' . e($row->employee->full_name) . '</b>
                                <br>
                                <small>' . e($row->employee->employee_code) . '</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('date_formatted', function ($row) {
                    return $row->attendance_date ? $row->attendance_date->format('d-m-Y') : '-';
                })
                ->addColumn('timing', function ($row) {
                    $in = $row->check_in ? \Carbon\Carbon::parse($row->check_in)->format('h:i A') : '-';
                    $out = $row->check_out ? \Carbon\Carbon::parse($row->check_out)->format('h:i A') : '-';
                    $line = $in . ' - ' . $out;

                    if ($row->overtime_hours > 0) {
                        $line .= '<br><small class="text-warning">OT: ' . number_format($row->overtime_hours, 2) . 'h</small>';
                    }

                    // PART 9's own example ("09:10 AM → Missing Checkout")
                    // — only for a genuinely past day, never today (still in
                    // progress) or a future-dated row.
                    if ($row->check_in && !$row->check_out && $row->attendance_date->isBefore(today())) {
                        $line .= '<br><span class="badge bg-danger-subtle text-danger"><i class="ri-error-warning-fill"></i> Missing Checkout</span>';
                    }

                    return $line;
                })
                ->addColumn('location', function ($row) {
                    return $this->locationBadges($row);
                })
                ->addColumn('attendance_status_badge', function ($row) {
                    $map = [
                        'present' => 'success',
                        'absent' => 'danger',
                        'half_day' => 'warning',
                        'on_leave' => 'info',
                        'late' => 'warning',
                        'early_leave' => 'warning',
                    ];
                    $color = $map[$row->attendance_status] ?? 'secondary';
                    $label = ucwords(str_replace('_', ' ', $row->attendance_status));
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . $label . '</span>';
                })
                ->addColumn('adjustment_badge', function ($row) {
                    return $this->adjustmentIndicator($row);
                })
                ->addColumn('action', function ($row) {
                    return view('admin.attendances.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'timing', 'location', 'attendance_status_badge', 'adjustment_badge', 'action'])
                ->make(true);
        }

        return view('admin.attendances.index');
    }

    /**
     * PART 9 integration: shows the adjustment status for this exact
     * employee+date (Pending/Approved/Rejected), and — reusing the EXISTING
     * AttendanceAdjustment module, no duplicate correction system — a
     * "Request Adjustment" quick action that opens its create form
     * pre-filled, whenever it's actually eligible (a genuinely past-or-today
     * date with no already-pending request for it).
     */
    private function adjustmentIndicator(Attendance $row): string
    {
        $adjustment = $this->matchingAdjustment($row);
        $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];

        $html = '';
        if ($adjustment) {
            $color = $map[$adjustment->approval_status] ?? 'secondary';
            $html .= '<span class="badge bg-' . $color . '-subtle text-' . $color . '">'
                . ucfirst($adjustment->approval_status) . '</span> ';
        }

        $alreadyPending = $adjustment && $adjustment->approval_status === 'pending';
        $canRequest = !$row->attendance_date->isAfter(today())
            && !$alreadyPending
            && auth()->guard('admin')->user()?->can('attendance-adjustment.create');

        if ($canRequest) {
            $url = route('admin.attendance-adjustments.create', [
                'employee_id' => $row->employee_id,
                'date' => $row->attendance_date->toDateString(),
            ]);
            $html .= '<button class="tl-icon-btn" id="openModal" data-url="' . $url . '" title="Request Adjustment">'
                . '<i class="ri-edit-2-line"></i></button>';
        } elseif ($html === '') {
            $html = '<span class="text-muted">—</span>';
        }

        return $html;
    }

    /**
     * Matches this row's employee_id+attendance_date against the batch-
     * eager-loaded employeeAdjustments relation (Attendance::index() eager-
     * loads it once per page) — an in-memory lookup, never a per-row query.
     * Pending requests are matched first (the ones an admin most needs to
     * see), falling back to the most recent decided one otherwise.
     */
    private function matchingAdjustment(Attendance $row): ?AttendanceAdjustment
    {
        $sameDate = $row->relationLoaded('employeeAdjustments')
            ? $row->employeeAdjustments->filter(fn ($adj) => $adj->adjustment_date->isSameDay($row->attendance_date))
            : collect();

        return $sameDate->firstWhere('approval_status', 'pending')
            ?? $sameDate->sortByDesc('id')->first();
    }

    /**
     * Turns the raw check_in/check_out lat/long columns into something an
     * admin can actually read at a glance: a "View on map" link per punch
     * (opens Google Maps, no API key/package needed for that), plus — when
     * an office location is configured in HRM Settings — a small distance
     * badge so it's obvious whether a punch happened near the office or not.
     */
    private function locationBadges(Attendance $row): string
    {
        $officeLat = get_settings('hrm_office_latitude');
        $officeLng = get_settings('hrm_office_longitude');
        $hasOffice = $officeLat !== null && $officeLat !== '' && $officeLng !== null && $officeLng !== '';

        $punch = function (?float $lat, ?float $lng, string $label) use ($hasOffice, $officeLat, $officeLng) {
            if ($lat === null || $lng === null) {
                return '<div class="small text-muted">' . $label . ': —</div>';
            }

            $mapUrl = "https://www.google.com/maps?q={$lat},{$lng}";
            $link = '<a href="' . $mapUrl . '" target="_blank" rel="noopener" class="small">'
                . '<i class="ri-map-pin-2-line"></i> ' . $label . '</a>';

            if (!$hasOffice) {
                return '<div>' . $link . '</div>';
            }

            $distance = \App\Http\Controllers\Admin\AttendancePortalController::distanceInMeters(
                (float) $officeLat, (float) $officeLng, $lat, $lng
            );
            $radius = (float) get_settings('hrm_geofence_radius_meters', 200);
            $withinRange = $distance <= $radius;
            $distanceLabel = $distance >= 1000 ? round($distance / 1000, 1) . 'km' : round($distance) . 'm';
            $badgeColor = $withinRange ? 'success' : 'danger';

            return '<div>' . $link
                . ' <span class="badge bg-' . $badgeColor . '-subtle text-' . $badgeColor . '">' . $distanceLabel . ' from office</span>'
                . '</div>';
        };

        return $punch($row->check_in_latitude, $row->check_in_longitude, 'In')
            . $punch($row->check_out_latitude, $row->check_out_longitude, 'Out');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.attendances.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttendanceRequest $request)
    {
        DB::beginTransaction();

        try {
            $attendance = $this->attendanceService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendances',
                'action' => 'create',
                'model' => 'Attendance',
                'model_id' => $attendance->id,
                'description' => 'Attendance recorded for employee #' . $attendance->employee_id,
                'new_data' => $attendance->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attendance recorded successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        $employees = Employee::active()->get();

        return view('admin.attendances.edit', compact('attendance', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttendanceRequest $request, Attendance $attendance)
    {
        DB::beginTransaction();

        try {
            $oldData = $attendance->toArray();
            $updatedAttendance = $this->attendanceService->update($attendance, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendances',
                'action' => 'update',
                'model' => 'Attendance',
                'model_id' => $attendance->id,
                'description' => 'Attendance updated for employee #' . $attendance->employee_id,
                'new_data' => $updatedAttendance->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.attendances.index'),
                'message' => 'Attendance updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        DB::beginTransaction();

        try {
            $oldData = $attendance->toArray();

            $this->attendanceService->delete($attendance);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendances',
                'action' => 'delete',
                'model' => 'Attendance',
                'model_id' => $oldData['id'],
                'description' => 'Attendance deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attendance deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status (AJAX switch toggle)
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = Attendance::find($id);
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.'
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.'
        ]);
    }
}
