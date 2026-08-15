<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectMilestoneRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ProjectMilestoneService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectMilestoneController extends Controller
{
    use ActivityLogger;

    protected $projectMilestoneService;

    public function __construct(ProjectMilestoneService $projectMilestoneService)
    {
        $this->projectMilestoneService = $projectMilestoneService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectMilestone::with(['project.client', 'assignedTo']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->milestone_status) {
                $query->where('milestone_status', $request->milestone_status);
            }

            if ($request->assigned_to) {
                $query->where('assigned_to', $request->assigned_to);
            }

            // Overdue is a computed condition (no stored flag), so it is
            // expressed as the same date comparison the accessor uses.
            if ($request->overdue) {
                $query->whereDate('due_date', '<', now()->toDateString())
                    ->whereNotIn('milestone_status', ProjectMilestone::CLOSED_STATUSES);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                                ->orWhere('project_code', 'like', "%{$search}%");
                        });
                });
            }

            // Soonest due first — this is a delivery worklist, not a log, the
            // same ordering reasoning as the Maintenance Schedule screen.
            $query->orderBy('due_date')->orderBy('sort_order');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-milestones.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b><br><small>#' . $row->sort_order . '</small>';
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
                ->addColumn('due_col', function ($row) {
                    $line = $row->due_date->format('d M Y');

                    if ($row->milestone_status === 'completed' && $row->completed_date) {
                        $class = $row->was_on_time ? 'text-success' : 'text-warning';
                        $label = $row->was_on_time ? 'On time' : 'Late';
                        $line .= '<br><small class="' . $class . '">' . $label . ' · ' . $row->completed_date->format('d M Y') . '</small>';
                    } elseif ($row->is_overdue) {
                        $late = abs($row->days_remaining);
                        $line .= '<br><small class="text-danger">Overdue by ' . $late . ' ' . \Illuminate\Support\Str::plural('day', $late) . '</small>';
                    } elseif ($row->days_remaining !== null) {
                        $line .= '<br><small>' . ($row->days_remaining === 0 ? 'Due today' : 'in ' . $row->days_remaining . ' ' . \Illuminate\Support\Str::plural('day', $row->days_remaining)) . '</small>';
                    }

                    return $line;
                })
                ->addColumn('milestone_status_badge', function ($row) {
                    $map = [
                        'pending' => 'bg-secondary',
                        'in_progress' => 'bg-primary',
                        'completed' => 'bg-success',
                        'delayed' => 'bg-warning',
                        'cancelled' => 'bg-danger',
                    ];

                    return '<span class="badge ' . ($map[$row->milestone_status] ?? 'bg-secondary') . '">' . e($row->milestone_status_label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-milestones.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name_col', 'project_name', 'owner', 'due_col', 'milestone_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.project-milestones.index', $this->formData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-milestones.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectMilestoneRequest $request)
    {
        DB::beginTransaction();

        try {
            $milestone = $this->projectMilestoneService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-milestones',
                'action' => 'create',
                'model' => 'ProjectMilestone',
                'model_id' => $milestone->id,
                'description' => 'Milestone "' . $milestone->name . '" created',
                'new_data' => $milestone->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Milestone created successfully.'
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
    public function edit(ProjectMilestone $projectMilestone)
    {
        return view('admin.project-milestones.edit', array_merge($this->formData(), [
            'projectMilestone' => $projectMilestone,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectMilestoneRequest $request, ProjectMilestone $projectMilestone)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectMilestone->toArray();
            $updatedMilestone = $this->projectMilestoneService->update($projectMilestone, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-milestones',
                'action' => 'update',
                'model' => 'ProjectMilestone',
                'model_id' => $projectMilestone->id,
                'description' => 'Milestone "' . $updatedMilestone->name . '" updated',
                'new_data' => $updatedMilestone->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-milestones.index'),
                'message' => 'Milestone updated successfully.'
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
    public function destroy(ProjectMilestone $projectMilestone)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectMilestone->toArray();

            $this->projectMilestoneService->delete($projectMilestone);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-milestones',
                'action' => 'delete',
                'model' => 'ProjectMilestone',
                'model_id' => $oldData['id'],
                'description' => 'Milestone "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Milestone deleted successfully.'
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

        $model = ProjectMilestone::find($id);
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
     */
    protected function formData(): array
    {
        return [
            'projects' => Project::active()->with('client')->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
