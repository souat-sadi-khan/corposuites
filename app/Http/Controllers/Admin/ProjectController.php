<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Client;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Services\ProjectService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    use ActivityLogger;

    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Project::with(['client', 'department', 'projectManager']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->client_id) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->project_status) {
                $query->where('project_status', $request->project_status);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            // Overdue is a computed condition (no stored flag), so it is
            // expressed as a date comparison scoped to still-open projects.
            if ($request->overdue) {
                $query->whereNotNull('end_date')
                    ->whereDate('end_date', '<', now()->toDateString())
                    ->whereNotIn('project_status', Project::CLOSED_STATUSES);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('project_code', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%")
                                ->orWhere('client_code', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.projects.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b><br><small>' . e($row->project_code) . '</small>';
                })
                ->addColumn('client_name', function ($row) {
                    if (! $row->client) {
                        return '<span class="text-danger">Client removed</span>';
                    }

                    return e($row->client->name) . '<br><small>' . e($row->client->client_code) . '</small>';
                })
                ->addColumn('manager_col', function ($row) {
                    $manager = $row->projectManager
                        ? e($row->projectManager->first_name . ' ' . $row->projectManager->last_name)
                        : '<span class="text-muted">Unassigned</span>';

                    return $manager . ($row->department ? '<br><small>' . e($row->department->name) . '</small>' : '');
                })
                ->addColumn('timeline', function ($row) {
                    $line = $row->start_date->format('d M Y') . ' → '
                        . ($row->end_date ? $row->end_date->format('d M Y') : 'Open-ended');

                    if ($row->is_overdue) {
                        $late = abs($row->days_remaining);
                        $line .= '<br><small class="text-danger">Overdue by ' . $late . ' ' . \Illuminate\Support\Str::plural('day', $late) . '</small>';
                    } elseif ($row->project_status === 'completed' && $row->actual_end_date) {
                        $line .= '<br><small class="text-success">Completed ' . $row->actual_end_date->format('d M Y') . '</small>';
                    } elseif ($row->days_remaining !== null) {
                        $line .= '<br><small>' . ($row->days_remaining === 0 ? 'Due today' : 'in ' . $row->days_remaining . ' ' . \Illuminate\Support\Str::plural('day', $row->days_remaining)) . '</small>';
                    }

                    return $line;
                })
                ->addColumn('progress_col', function ($row) {
                    $pct = (int) $row->progress_percent;
                    $class = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-primary' : 'bg-secondary');

                    return '<div class="progress" style="height:6px;"><div class="progress-bar ' . $class . '" style="width:' . $pct . '%"></div></div><small>' . $pct . '%</small>';
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
                ->addColumn('project_status_badge', function ($row) {
                    $map = [
                        'planned' => 'bg-secondary',
                        'in_progress' => 'bg-primary',
                        'on_hold' => 'bg-warning',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                    ];

                    return '<span class="badge ' . ($map[$row->project_status] ?? 'bg-secondary') . '">' . e($row->project_status_label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.projects.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'client_name', 'manager_col', 'timeline', 'progress_col', 'priority_badge', 'project_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.projects.index', [
            'clients' => Client::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.projects.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectRequest $request)
    {
        DB::beginTransaction();

        try {
            $project = $this->projectService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'projects',
                'action' => 'create',
                'model' => 'Project',
                'model_id' => $project->id,
                'description' => 'Project "' . $project->name . ' (' . $project->project_code . ')" created',
                'new_data' => $project->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Project created successfully.'
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
    public function edit(Project $project)
    {
        return view('admin.projects.edit', array_merge($this->formData(), compact('project')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectRequest $request, Project $project)
    {
        DB::beginTransaction();

        try {
            $oldData = $project->toArray();
            $updatedProject = $this->projectService->update($project, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'projects',
                'action' => 'update',
                'model' => 'Project',
                'model_id' => $project->id,
                'description' => 'Project "' . $updatedProject->name . ' (' . $updatedProject->project_code . ')" updated',
                'new_data' => $updatedProject->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.projects.index'),
                'message' => 'Project updated successfully.'
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
    public function destroy(Project $project)
    {
        DB::beginTransaction();

        try {
            $oldData = $project->toArray();

            $this->projectService->delete($project);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'projects',
                'action' => 'delete',
                'model' => 'Project',
                'model_id' => $oldData['id'],
                'description' => 'Project "' . $oldData['name'] . ' (' . $oldData['project_code'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Project deleted successfully.'
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

        $model = Project::find($id);
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
     * Dropdown collections shared by create and edit.
     */
    protected function formData(): array
    {
        return [
            'clients' => Client::active()->orderBy('name')->get(),
            'departments' => Department::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
