<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectTimeEntryRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTimeEntry;
use App\Services\ProjectTimeEntryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectTimeEntryController extends Controller
{
    use ActivityLogger;

    protected $projectTimeEntryService;

    public function __construct(ProjectTimeEntryService $projectTimeEntryService)
    {
        $this->projectTimeEntryService = $projectTimeEntryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectTimeEntry::with(['employee', 'project.client', 'task', 'projectTimesheet']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->billable !== null && $request->billable !== '') {
                $query->where('is_billable', $request->billable);
            }

            if ($request->running) {
                $query->running();
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                                ->orWhere('project_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('employee', function ($e) use ($search) {
                            $e->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('employee_code', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('work_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-time-entries.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee
                        ? '<b class="tl-name-txt">' . e($row->employee->first_name . ' ' . $row->employee->last_name) . '</b><br><small>' . e($row->employee->employee_code) . '</small>'
                        : '<span class="text-danger">Employee removed</span>';
                })
                ->addColumn('project_col', function ($row) {
                    if (! $row->project) {
                        return '<span class="text-danger">Project removed</span>';
                    }

                    return e($row->project->name) . '<br><small>' . e($row->project->project_code)
                        . ($row->task ? ' · ' . e($row->task->title) : '') . '</small>';
                })
                ->addColumn('work_date_formatted', function ($row) {
                    return $row->work_date->format('d M Y');
                })
                ->addColumn('duration_col', function ($row) {
                    if ($row->is_running) {
                        $mins = $row->elapsed_minutes;
                        return '<span class="badge bg-primary"><i class="ri-timer-line"></i> Running</span><br><small>'
                            . sprintf('%dh %02dm so far', intdiv($mins, 60), $mins % 60) . '</small>';
                    }

                    if ($row->started_at && $row->ended_at) {
                        return number_format($row->hours, 2) . 'h<br><small>'
                            . $row->started_at->format('H:i') . ' - ' . $row->ended_at->format('H:i') . '</small>';
                    }

                    return $row->hours ? number_format($row->hours, 2) . 'h<br><small>Manually entered</small>' : '—';
                })
                ->addColumn('billable_badge', function ($row) {
                    $badge = $row->is_billable
                        ? '<span class="badge bg-success">Billable</span>'
                        : '<span class="badge bg-secondary">Non-billable</span>';

                    if ($row->is_locked) {
                        $badge .= '<br><small class="text-muted"><i class="ri-lock-line"></i> On a submitted timesheet</small>';
                    }

                    return $badge;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-time-entries.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'project_col', 'duration_col', 'billable_badge', 'action'])
                ->make(true);
        }

        return view('admin.project-time-entries.index', array_merge($this->formData(), [
            'myEmployeeId' => $this->currentEmployeeId(),
            'runningEntry' => $this->currentEmployeeId()
                ? ProjectTimeEntry::running()->where('employee_id', $this->currentEmployeeId())->with('project')->first()
                : null,
        ]));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-time-entries.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectTimeEntryRequest $request)
    {
        DB::beginTransaction();

        try {
            $entry = $this->projectTimeEntryService->create($request->validated());
            $entry->load(['employee', 'project']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-time-entries',
                'action' => 'create',
                'model' => 'ProjectTimeEntry',
                'model_id' => $entry->id,
                'description' => 'Time entry "' . $this->entryLabel($entry) . '" logged',
                'new_data' => $entry->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Time entry logged successfully.'
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
    public function edit(ProjectTimeEntry $projectTimeEntry)
    {
        return view('admin.project-time-entries.edit', array_merge($this->formData(), [
            'projectTimeEntry' => $projectTimeEntry,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectTimeEntryRequest $request, ProjectTimeEntry $projectTimeEntry)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectTimeEntry->toArray();
            $updated = $this->projectTimeEntryService->update($projectTimeEntry, $request->validated());
            $updated->load(['employee', 'project']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-time-entries',
                'action' => 'update',
                'model' => 'ProjectTimeEntry',
                'model_id' => $projectTimeEntry->id,
                'description' => 'Time entry "' . $this->entryLabel($updated) . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-time-entries.index'),
                'message' => 'Time entry updated successfully.'
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
    public function destroy(ProjectTimeEntry $projectTimeEntry)
    {
        DB::beginTransaction();

        try {
            $projectTimeEntry->load(['employee', 'project']);
            $oldData = $projectTimeEntry->toArray();
            $label = $this->entryLabel($projectTimeEntry);

            $this->projectTimeEntryService->delete($projectTimeEntry);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-time-entries',
                'action' => 'delete',
                'model' => 'ProjectTimeEntry',
                'model_id' => $oldData['id'],
                'description' => 'Time entry "' . $label . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Time entry deleted successfully.'
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

        $model = ProjectTimeEntry::find($id);
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
     * Small quick-start form (Project + Task only) for the current admin's
     * own linked employee — separate from the full create form, which is
     * for logging finished/manual entries on anyone's behalf.
     */
    public function startTimerForm()
    {
        $employeeId = $this->currentEmployeeId();

        if (! $employeeId) {
            return response('<div class="modal-body p-4 text-center text-muted">Your admin account isn\'t linked to an employee record, so you can\'t run your own timer. Use "Add Time Entry" to log time on someone\'s behalf instead.</div>');
        }

        return view('admin.project-time-entries.start-timer', [
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'tasks' => ProjectTask::active()->orderBy('title')->get(),
        ]);
    }

    /**
     * Start a timer for the current admin's own linked employee.
     */
    public function startTimer(Request $request)
    {
        $employeeId = $this->currentEmployeeId();

        if (! $employeeId) {
            return response()->json([
                'status' => false,
                'message' => "Your admin account isn't linked to an employee record."
            ], 422);
        }

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'project_task_id' => 'nullable|exists:project_tasks,id',
            'is_billable' => 'nullable|boolean',
        ]);

        try {
            $entry = $this->projectTimeEntryService->startTimer(
                $employeeId,
                (int) $request->project_id,
                $request->project_task_id ? (int) $request->project_task_id : null,
                $request->boolean('is_billable', true)
            );

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-time-entries',
                'action' => 'start-timer',
                'model' => 'ProjectTimeEntry',
                'model_id' => $entry->id,
                'description' => 'Timer started on "' . ($entry->project->name ?? 'project') . '"',
                'new_data' => $entry->toArray(),
                'old_data' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Timer started.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Stop the current admin's own running timer.
     */
    public function stopTimer(Request $request, ProjectTimeEntry $projectTimeEntry)
    {
        if ((int) $projectTimeEntry->employee_id !== (int) $this->currentEmployeeId()) {
            return response()->json([
                'status' => false,
                'message' => 'You can only stop your own timer.'
            ], 403);
        }

        try {
            $oldData = $projectTimeEntry->toArray();
            $updated = $this->projectTimeEntryService->stopTimer($projectTimeEntry, $request->input('description'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-time-entries',
                'action' => 'stop-timer',
                'model' => 'ProjectTimeEntry',
                'model_id' => $updated->id,
                'description' => 'Timer stopped after ' . number_format($updated->hours, 2) . 'h',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Timer stopped — ' . number_format($updated->hours, 2) . ' hours logged.'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * The employee record linked to the currently authenticated admin, if
     * any — not every admin account is tied to one.
     */
    protected function currentEmployeeId(): ?int
    {
        return auth()->guard('admin')->user()?->employee_id;
    }

    protected function entryLabel(ProjectTimeEntry $entry): string
    {
        $employee = $entry->employee
            ? $entry->employee->first_name . ' ' . $entry->employee->last_name
            : 'Unknown employee';

        return $employee . ' on ' . ($entry->project->project_code ?? 'unknown project');
    }

    /**
     * Dropdown collections shared by index, create and edit. Tasks carry
     * their project id so the form can narrow the list client-side, the
     * same technique ProjectTaskController uses for its milestone select.
     */
    protected function formData(): array
    {
        return [
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'tasks' => ProjectTask::active()->orderBy('title')->get(),
        ];
    }
}
