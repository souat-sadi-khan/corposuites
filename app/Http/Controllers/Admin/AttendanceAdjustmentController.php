<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttendanceAdjustmentRequest;
use App\Models\AttendanceAdjustment;
use App\Models\Employee;
use App\Models\WorkflowDefinition;
use App\Services\AttendanceAdjustmentService;
use App\Services\WorkflowEngineService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AttendanceAdjustmentController extends Controller
{
    use ActivityLogger;

    protected $attendanceAdjustmentService;

    public function __construct(AttendanceAdjustmentService $attendanceAdjustmentService)
    {
        $this->attendanceAdjustmentService = $attendanceAdjustmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AttendanceAdjustment::query()->with('employee');

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
                    $q->where('reason', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.attendance-adjustments.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
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
                    return $row->adjustment_date ? $row->adjustment_date->format('d-m-Y') : '-';
                })
                ->addColumn('requested_timing', function ($row) {
                    $in = $row->requested_check_in ? \Carbon\Carbon::parse($row->requested_check_in)->format('h:i A') : '-';
                    $out = $row->requested_check_out ? \Carbon\Carbon::parse($row->requested_check_out)->format('h:i A') : '-';
                    return $in . ' - ' . $out;
                })
                ->addColumn('approval_badge', function ($row) {
                    $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    $color = $map[$row->approval_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . ucfirst($row->approval_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.attendance-adjustments.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'approval_badge', 'action'])
                ->make(true);
        }

        return view('admin.attendance-adjustments.index');
    }

    /**
     * Show the form for creating a new resource. When reached via the
     * "Request Adjustment" quick action (Attendance list/Monthly Sheet —
     * PART 9), employee_id+date arrive as query params: pre-fills the date,
     * shows what's actually recorded for that day as context (same
     * convenience the self-service adjustment form already gives an
     * employee requesting their own correction), and flags an
     * already-pending request for that exact employee+date so an admin
     * doesn't unknowingly file a duplicate.
     */
    public function create(Request $request)
    {
        $employees = Employee::active()->get();

        $prefillEmployeeId = $request->input('employee_id');
        $prefillDate = $request->filled('date') ? \Carbon\Carbon::parse($request->input('date')) : null;

        $existingAttendance = null;
        $pendingExists = false;
        if ($prefillEmployeeId && $prefillDate) {
            $existingAttendance = \App\Models\Attendance::where('employee_id', $prefillEmployeeId)
                ->whereDate('attendance_date', $prefillDate)
                ->first();
            $pendingExists = AttendanceAdjustment::where('employee_id', $prefillEmployeeId)
                ->whereDate('adjustment_date', $prefillDate)
                ->where('approval_status', 'pending')
                ->exists();
        }

        return view('admin.attendance-adjustments.create', compact(
            'employees', 'prefillDate', 'existingAttendance', 'pendingExists'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttendanceAdjustmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $attendanceAdjustment = $this->attendanceAdjustmentService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendance-adjustments',
                'action' => 'create',
                'model' => 'AttendanceAdjustment',
                'model_id' => $attendanceAdjustment->id,
                'description' => 'Attendance adjustment requested for employee #' . $attendanceAdjustment->employee_id,
                'new_data' => $attendanceAdjustment->toArray(),
                'old_data' => null
            ]);

            // If an active Workflow Engine definition exists for AttendanceAdjustment, kick off
            // an approval instance. No definitions are seeded yet, so this is a no-op today.
            if (WorkflowDefinition::where('approvable_type', AttendanceAdjustment::class)->where('status', true)->exists()) {
                app(WorkflowEngineService::class)->start($attendanceAdjustment, auth()->guard('admin')->id());
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attendance adjustment requested successfully.'
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
    public function edit(AttendanceAdjustment $attendanceAdjustment)
    {
        $employees = Employee::active()->get();

        return view('admin.attendance-adjustments.edit', compact('attendanceAdjustment', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttendanceAdjustmentRequest $request, AttendanceAdjustment $attendanceAdjustment)
    {
        DB::beginTransaction();

        try {
            $oldData = $attendanceAdjustment->toArray();
            $updatedAttendanceAdjustment = $this->attendanceAdjustmentService->update($attendanceAdjustment, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendance-adjustments',
                'action' => 'update',
                'model' => 'AttendanceAdjustment',
                'model_id' => $attendanceAdjustment->id,
                'description' => 'Attendance adjustment updated for employee #' . $attendanceAdjustment->employee_id,
                'new_data' => $updatedAttendanceAdjustment->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.attendance-adjustments.index'),
                'message' => 'Attendance adjustment updated successfully.'
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
    public function destroy(AttendanceAdjustment $attendanceAdjustment)
    {
        DB::beginTransaction();

        try {
            $oldData = $attendanceAdjustment->toArray();

            $this->attendanceAdjustmentService->delete($attendanceAdjustment);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendance-adjustments',
                'action' => 'delete',
                'model' => 'AttendanceAdjustment',
                'model_id' => $oldData['id'],
                'description' => 'Attendance adjustment deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attendance adjustment deleted successfully.'
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
     * Approve the adjustment and sync into Attendance.
     */
    public function approve(AttendanceAdjustment $attendanceAdjustment)
    {
        DB::beginTransaction();

        try {
            $oldData = $attendanceAdjustment->toArray();

            // Fallback-safe: only route through the Workflow Engine when an active
            // WorkflowDefinition exists for AttendanceAdjustment. Today none exists, so
            // this always takes the else branch — the exact original behavior.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', AttendanceAdjustment::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $attendanceAdjustment->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'approved');
                } else {
                    $this->attendanceAdjustmentService->approve($attendanceAdjustment);
                }
            } else {
                $this->attendanceAdjustmentService->approve($attendanceAdjustment);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendance-adjustments',
                'action' => 'approve',
                'model' => 'AttendanceAdjustment',
                'model_id' => $attendanceAdjustment->id,
                'description' => 'Attendance adjustment approved for employee #' . $attendanceAdjustment->employee_id,
                'new_data' => $attendanceAdjustment->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attendance adjustment approved.'
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
     * Reject the adjustment.
     */
    public function reject(AttendanceAdjustment $attendanceAdjustment)
    {
        DB::beginTransaction();

        try {
            $oldData = $attendanceAdjustment->toArray();

            // Fallback-safe: same pattern as approve() above.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', AttendanceAdjustment::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $attendanceAdjustment->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'rejected');
                } else {
                    $this->attendanceAdjustmentService->reject($attendanceAdjustment);
                }
            } else {
                $this->attendanceAdjustmentService->reject($attendanceAdjustment);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'attendance-adjustments',
                'action' => 'reject',
                'model' => 'AttendanceAdjustment',
                'model_id' => $attendanceAdjustment->id,
                'description' => 'Attendance adjustment rejected for employee #' . $attendanceAdjustment->employee_id,
                'new_data' => $attendanceAdjustment->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Attendance adjustment rejected.'
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

        $model = AttendanceAdjustment::find($id);
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
