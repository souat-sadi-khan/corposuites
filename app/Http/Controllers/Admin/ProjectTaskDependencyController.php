<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectTaskDependencyRequest;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDependency;
use App\Services\ProjectTaskDependencyService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectTaskDependencyController extends Controller
{
    use ActivityLogger;

    protected $projectTaskDependencyService;

    public function __construct(ProjectTaskDependencyService $projectTaskDependencyService)
    {
        $this->projectTaskDependencyService = $projectTaskDependencyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectTaskDependency::with(['task.project', 'dependsOnTask']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->project_id) {
                $projectId = $request->project_id;
                $query->whereHas('task', fn ($q) => $q->where('project_id', $projectId));
            }

            if ($request->dependency_type) {
                $query->where('dependency_type', $request->dependency_type);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('task', function ($t) use ($search) {
                        $t->where('title', 'like', "%{$search}%")
                            ->orWhere('task_code', 'like', "%{$search}%");
                    })->orWhereHas('dependsOnTask', function ($t) use ($search) {
                        $t->where('title', 'like', "%{$search}%")
                            ->orWhere('task_code', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-task-dependencies.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('project_name', function ($row) {
                    $project = $row->task?->project;

                    if (! $project) {
                        return '<span class="text-danger">Project removed</span>';
                    }

                    return e($project->name) . '<br><small>' . e($project->project_code) . '</small>';
                })
                ->addColumn('link_col', function ($row) {
                    $predecessor = $row->dependsOnTask
                        ? e($row->dependsOnTask->title) . ' <small>(' . e($row->dependsOnTask->task_code) . ')</small>'
                        : '<span class="text-danger">Task removed</span>';

                    $successor = $row->task
                        ? e($row->task->title) . ' <small>(' . e($row->task->task_code) . ')</small>'
                        : '<span class="text-danger">Task removed</span>';

                    return $predecessor . ' <i class="ri-arrow-right-line text-muted"></i> ' . $successor;
                })
                ->addColumn('type_badge', function ($row) {
                    return '<span class="badge bg-info">' . e($row->dependency_type_label) . '</span>';
                })
                ->addColumn('lag_label', function ($row) {
                    if (! $row->lag_days) {
                        return '<span class="text-muted">None</span>';
                    }

                    return $row->lag_days > 0
                        ? '+' . $row->lag_days . ' day' . ($row->lag_days == 1 ? '' : 's') . ' lag'
                        : $row->lag_days . ' day' . ($row->lag_days == -1 ? '' : 's') . ' lead';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-task-dependencies.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'project_name', 'link_col', 'type_badge', 'lag_label', 'action'])
                ->make(true);
        }

        return view('admin.project-task-dependencies.index', $this->formData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-task-dependencies.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectTaskDependencyRequest $request)
    {
        DB::beginTransaction();

        try {
            $dependency = $this->projectTaskDependencyService->create($request->validated());
            $dependency->load(['task', 'dependsOnTask']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-task-dependencies',
                'action' => 'create',
                'model' => 'ProjectTaskDependency',
                'model_id' => $dependency->id,
                'description' => 'Dependency "' . $this->dependencyLabel($dependency) . '" created',
                'new_data' => $dependency->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Dependency added successfully.'
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
    public function edit(ProjectTaskDependency $projectTaskDependency)
    {
        return view('admin.project-task-dependencies.edit', array_merge($this->formData(), [
            'projectTaskDependency' => $projectTaskDependency,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectTaskDependencyRequest $request, ProjectTaskDependency $projectTaskDependency)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectTaskDependency->toArray();
            $updated = $this->projectTaskDependencyService->update($projectTaskDependency, $request->validated());
            $updated->load(['task', 'dependsOnTask']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-task-dependencies',
                'action' => 'update',
                'model' => 'ProjectTaskDependency',
                'model_id' => $projectTaskDependency->id,
                'description' => 'Dependency "' . $this->dependencyLabel($updated) . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-task-dependencies.index'),
                'message' => 'Dependency updated successfully.'
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
    public function destroy(ProjectTaskDependency $projectTaskDependency)
    {
        DB::beginTransaction();

        try {
            $projectTaskDependency->load(['task', 'dependsOnTask']);
            $oldData = $projectTaskDependency->toArray();
            $label = $this->dependencyLabel($projectTaskDependency);

            $this->projectTaskDependencyService->delete($projectTaskDependency);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-task-dependencies',
                'action' => 'delete',
                'model' => 'ProjectTaskDependency',
                'model_id' => $oldData['id'],
                'description' => 'Dependency "' . $label . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Dependency deleted successfully.'
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

        $model = ProjectTaskDependency::find($id);
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
     * Readable "Predecessor -> Successor" label used in the activity log.
     */
    protected function dependencyLabel(ProjectTaskDependency $dependency): string
    {
        $predecessor = $dependency->dependsOnTask->title ?? 'Unknown task';
        $successor = $dependency->task->title ?? 'Unknown task';

        return $predecessor . ' -> ' . $successor;
    }

    /**
     * Dropdown collections shared by index, create and edit. Tasks carry
     * their project id so the form can narrow both selects to one project
     * client-side — the Form Request rejects a cross-project pair, so the
     * form does not offer one in the first place.
     */
    protected function formData(): array
    {
        return [
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'tasks' => ProjectTask::active()->orderBy('title')->get(),
        ];
    }
}
