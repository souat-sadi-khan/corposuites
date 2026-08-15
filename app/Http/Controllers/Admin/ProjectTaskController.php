<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectTaskRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Services\ProjectTaskService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectTaskController extends Controller
{
    use ActivityLogger;

    protected $projectTaskService;

    public function __construct(ProjectTaskService $projectTaskService)
    {
        $this->projectTaskService = $projectTaskService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectTask::with(['project.client', 'milestone', 'assignedTo']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->task_status) {
                $query->where('task_status', $request->task_status);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            if ($request->assigned_to) {
                $query->where('assigned_to', $request->assigned_to);
            }

            // Overdue is a computed condition (no stored flag), so it is
            // expressed as the same date comparison the accessor uses.
            if ($request->overdue) {
                $query->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->open();
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('task_code', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                                ->orWhere('project_code', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-tasks.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('title_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->title) . '</b><br><small>' . e($row->task_code)
                        . ($row->milestone ? ' · ' . e($row->milestone->name) : '') . '</small>';
                })
                ->addColumn('project_name', function ($row) {
                    if (! $row->project) {
                        return '<span class="text-danger">Project removed</span>';
                    }

                    return e($row->project->name) . '<br><small>' . e($row->project->project_code)
                        . ($row->project->client ? ' · ' . e($row->project->client->name) : '') . '</small>';
                })
                ->addColumn('owner', function ($row) {
                    return $row->assignedTo
                        ? e($row->assignedTo->first_name . ' ' . $row->assignedTo->last_name)
                        : '<span class="text-muted">Unassigned</span>';
                })
                ->addColumn('schedule', function ($row) {
                    $line = ($row->start_date ? $row->start_date->format('d M Y') : '—') . ' → '
                        . ($row->due_date ? $row->due_date->format('d M Y') : 'No due date');

                    if ($row->task_status === 'done' && $row->completed_date) {
                        $line .= '<br><small class="text-success">Done ' . $row->completed_date->format('d M Y') . '</small>';
                    } elseif ($row->is_overdue) {
                        $late = abs($row->days_remaining);
                        $line .= '<br><small class="text-danger">Overdue by ' . $late . ' ' . \Illuminate\Support\Str::plural('day', $late) . '</small>';
                    } elseif ($row->days_remaining !== null) {
                        $line .= '<br><small>' . ($row->days_remaining === 0 ? 'Due today' : 'in ' . $row->days_remaining . ' ' . \Illuminate\Support\Str::plural('day', $row->days_remaining)) . '</small>';
                    }

                    return $line;
                })
                ->addColumn('progress_col', function ($row) {
                    $pct = (int) $row->progress_percent;
                    $class = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-primary' : 'bg-secondary');

                    $bar = '<div class="progress" style="height:6px;"><div class="progress-bar ' . $class . '" style="width:' . $pct . '%"></div></div><small>' . $pct . '%';

                    if ($row->estimated_hours) {
                        $bar .= ' · ' . rtrim(rtrim(number_format($row->estimated_hours, 2), '0'), '.') . 'h est';
                    }

                    return $bar . '</small>';
                })
                ->addColumn('priority_badge', function ($row) {
                    $map = [
                        'low' => 'bg-secondary',
                        'medium' => 'bg-info',
                        'high' => 'bg-warning',
                        'critical' => 'bg-danger',
                    ];

                    return '<span class="badge ' . ($map[$row->priority] ?? 'bg-secondary') . '">' . e($row->priority_label) . '</span>';
                })
                ->addColumn('task_status_badge', function ($row) {
                    $map = [
                        'todo' => 'bg-secondary',
                        'in_progress' => 'bg-primary',
                        'review' => 'bg-warning',
                        'done' => 'bg-success',
                        'cancelled' => 'bg-danger',
                    ];

                    return '<span class="badge ' . ($map[$row->task_status] ?? 'bg-secondary') . '">' . e($row->task_status_label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-tasks.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'title_col', 'project_name', 'owner', 'schedule', 'progress_col', 'priority_badge', 'task_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.project-tasks.index', $this->formData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-tasks.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectTaskRequest $request)
    {
        DB::beginTransaction();

        try {
            $task = $this->projectTaskService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-tasks',
                'action' => 'create',
                'model' => 'ProjectTask',
                'model_id' => $task->id,
                'description' => 'Task "' . $task->title . ' (' . $task->task_code . ')" created',
                'new_data' => $task->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Task created successfully.'
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
    public function edit(ProjectTask $projectTask)
    {
        return view('admin.project-tasks.edit', array_merge($this->formData(), [
            'projectTask' => $projectTask,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectTaskRequest $request, ProjectTask $projectTask)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectTask->toArray();
            $updatedTask = $this->projectTaskService->update($projectTask, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-tasks',
                'action' => 'update',
                'model' => 'ProjectTask',
                'model_id' => $projectTask->id,
                'description' => 'Task "' . $updatedTask->title . ' (' . $updatedTask->task_code . ')" updated',
                'new_data' => $updatedTask->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-tasks.index'),
                'message' => 'Task updated successfully.'
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
    public function destroy(ProjectTask $projectTask)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectTask->toArray();

            $this->projectTaskService->delete($projectTask);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-tasks',
                'action' => 'delete',
                'model' => 'ProjectTask',
                'model_id' => $oldData['id'],
                'description' => 'Task "' . $oldData['title'] . ' (' . $oldData['task_code'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Task deleted successfully.'
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

        $model = ProjectTask::find($id);
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
     * Dropdown collections shared by index, create and edit.
     *
     * Milestones carry their project id so the form can narrow the list to
     * the selected project client-side — no per-row AJAX.
     */
    protected function formData(): array
    {
        return [
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'milestones' => ProjectMilestone::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
