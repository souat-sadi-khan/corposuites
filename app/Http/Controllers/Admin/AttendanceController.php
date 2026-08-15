<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceRequest;
use App\Models\Attendance;
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
            $query = Attendance::query()->with('employee');

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
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('date_formatted', function ($row) {
                    return $row->attendance_date ? $row->attendance_date->format('d-m-Y') : '-';
                })
                ->addColumn('timing', function ($row) {
                    $in = $row->check_in ? \Carbon\Carbon::parse($row->check_in)->format('h:i A') : '-';
                    $out = $row->check_out ? \Carbon\Carbon::parse($row->check_out)->format('h:i A') : '-';
                    return $in . ' - ' . $out;
                })
                ->addColumn('attendance_status_badge', function ($row) {
                    $map = [
                        'present' => 'success',
                        'absent' => 'danger',
                        'half_day' => 'warning',
                        'on_leave' => 'info',
                        'late' => 'warning',
                    ];
                    $color = $map[$row->attendance_status] ?? 'secondary';
                    $label = ucwords(str_replace('_', ' ', $row->attendance_status));
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . $label . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.attendances.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'attendance_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.attendances.index');
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
