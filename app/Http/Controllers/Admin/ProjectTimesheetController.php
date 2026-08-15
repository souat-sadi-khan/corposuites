<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectTimesheetRequest;
use App\Http\Requests\Admin\ProjectTimesheetUpdateRequest;
use App\Models\Employee;
use App\Models\ProjectTimesheet;
use App\Services\ProjectTimesheetService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectTimesheetController extends Controller
{
    use ActivityLogger;

    protected $projectTimesheetService;

    public function __construct(ProjectTimesheetService $projectTimesheetService)
    {
        $this->projectTimesheetService = $projectTimesheetService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectTimesheet::with(['employee', 'approvedBy']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->timesheet_status) {
                $query->where('timesheet_status', $request->timesheet_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->whereHas('employee', function ($e) use ($search) {
                    $e->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            $query->orderBy('week_start_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-timesheets.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee
                        ? '<b class="tl-name-txt">' . e($row->employee->first_name . ' ' . $row->employee->last_name) . '</b><br><small>' . e($row->employee->employee_code) . '</small>'
                        : '<span class="text-danger">Employee removed</span>';
                })
                ->addColumn('week_col', function ($row) {
                    return e($row->week_label);
                })
                ->addColumn('hours_col', function ($row) {
                    return number_format($row->total_hours, 2) . 'h<br><small>' . number_format($row->billable_hours, 2) . 'h billable</small>';
                })
                ->addColumn('timesheet_status_badge', function ($row) {
                    $map = [
                        'draft' => 'bg-secondary',
                        'submitted' => 'bg-info',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                    ];

                    $badge = '<span class="badge ' . ($map[$row->timesheet_status] ?? 'bg-secondary') . '">' . e($row->timesheet_status_label) . '</span>';

                    if ($row->timesheet_status === 'approved' && $row->approvedBy) {
                        $badge .= '<br><small>by ' . e($row->approvedBy->name ?? $row->approvedBy->email) . '</small>';
                    } elseif ($row->timesheet_status === 'rejected' && $row->rejection_reason) {
                        $badge .= '<br><small class="text-danger">' . e(\Illuminate\Support\Str::limit($row->rejection_reason, 40)) . '</small>';
                    }

                    return $badge;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-timesheets.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'week_col', 'hours_col', 'timesheet_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.project-timesheets.index', $this->formData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-timesheets.create', $this->formData());
    }

    /**
     * "Store" here means generate/regenerate — it finds or creates the
     * draft header for the chosen employee+week and links their finished
     * time entries onto it.
     */
    public function store(ProjectTimesheetRequest $request)
    {
        DB::beginTransaction();

        try {
            $timesheet = $this->projectTimesheetService->generate((int) $request->employee_id, $request->week_start_date);
            $timesheet->load('employee');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-timesheets',
                'action' => 'generate',
                'model' => 'ProjectTimesheet',
                'model_id' => $timesheet->id,
                'description' => 'Timesheet "' . $this->timesheetLabel($timesheet) . '" generated',
                'new_data' => $timesheet->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Timesheet generated — ' . number_format($timesheet->total_hours, 2) . ' hours linked.'
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
    public function edit(ProjectTimesheet $projectTimesheet)
    {
        return view('admin.project-timesheets.edit', array_merge($this->formData(), [
            'projectTimesheet' => $projectTimesheet,
        ]));
    }

    /**
     * Update the specified resource in storage — notes and the archive
     * status toggle only.
     */
    public function update(ProjectTimesheetUpdateRequest $request, ProjectTimesheet $projectTimesheet)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectTimesheet->toArray();
            $updated = $this->projectTimesheetService->update($projectTimesheet, $request->validated());
            $updated->load('employee');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-timesheets',
                'action' => 'update',
                'model' => 'ProjectTimesheet',
                'model_id' => $projectTimesheet->id,
                'description' => 'Timesheet "' . $this->timesheetLabel($updated) . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-timesheets.index'),
                'message' => 'Timesheet updated successfully.'
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
    public function destroy(ProjectTimesheet $projectTimesheet)
    {
        DB::beginTransaction();

        try {
            $projectTimesheet->load('employee');
            $oldData = $projectTimesheet->toArray();
            $label = $this->timesheetLabel($projectTimesheet);

            $this->projectTimesheetService->delete($projectTimesheet);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-timesheets',
                'action' => 'delete',
                'model' => 'ProjectTimesheet',
                'model_id' => $oldData['id'],
                'description' => 'Timesheet "' . $label . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Timesheet deleted successfully.'
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

        $model = ProjectTimesheet::find($id);
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

    /**
     * Re-pull an employee's finished time entries for the same week —
     * only possible while the timesheet is still draft or was rejected.
     */
    public function regenerate(ProjectTimesheet $projectTimesheet)
    {
        try {
            $timesheet = $this->projectTimesheetService->generate(
                (int) $projectTimesheet->employee_id,
                $projectTimesheet->week_start_date->toDateString()
            );

            return response()->json([
                'status' => true,
                'message' => 'Timesheet regenerated — ' . number_format($timesheet->total_hours, 2) . ' hours linked.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function submitTimesheet(ProjectTimesheet $projectTimesheet)
    {
        try {
            $timesheet = $this->projectTimesheetService->submit($projectTimesheet);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-timesheets',
                'action' => 'submit',
                'model' => 'ProjectTimesheet',
                'model_id' => $timesheet->id,
                'description' => 'Timesheet "' . $this->timesheetLabel($timesheet) . '" submitted for approval',
                'new_data' => $timesheet->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Timesheet submitted for approval.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function approveTimesheet(ProjectTimesheet $projectTimesheet)
    {
        try {
            $timesheet = $this->projectTimesheetService->approve($projectTimesheet, auth()->guard('admin')->id());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-timesheets',
                'action' => 'approve',
                'model' => 'ProjectTimesheet',
                'model_id' => $timesheet->id,
                'description' => 'Timesheet "' . $this->timesheetLabel($timesheet) . '" approved',
                'new_data' => $timesheet->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Timesheet approved.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function rejectTimesheet(Request $request, ProjectTimesheet $projectTimesheet)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $timesheet = $this->projectTimesheetService->reject($projectTimesheet, $request->input('reason'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-timesheets',
                'action' => 'reject',
                'model' => 'ProjectTimesheet',
                'model_id' => $timesheet->id,
                'description' => 'Timesheet "' . $this->timesheetLabel($timesheet) . '" rejected',
                'new_data' => $timesheet->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Timesheet rejected.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    protected function timesheetLabel(ProjectTimesheet $timesheet): string
    {
        $employee = $timesheet->employee
            ? $timesheet->employee->first_name . ' ' . $timesheet->employee->last_name
            : 'Unknown employee';

        return $employee . ' — ' . $timesheet->week_label;
    }

    /**
     * Dropdown collections shared by index, create and edit.
     */
    protected function formData(): array
    {
        return [
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
