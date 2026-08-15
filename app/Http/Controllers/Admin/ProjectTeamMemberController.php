<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectTeamMemberRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use App\Services\ProjectTeamMemberService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectTeamMemberController extends Controller
{
    use ActivityLogger;

    protected $projectTeamMemberService;

    public function __construct(ProjectTeamMemberService $projectTeamMemberService)
    {
        $this->projectTeamMemberService = $projectTeamMemberService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProjectTeamMember::with(['project.client', 'employee.designation']);

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

            if ($request->team_role) {
                $query->where('team_role', $request->team_role);
            }

            // "Current" is a computed condition (no stored flag), so it is
            // expressed here as the same date comparison the model uses.
            if ($request->membership === 'current') {
                $query->current();
            } elseif ($request->membership === 'past') {
                $query->whereNotNull('left_date')
                    ->whereDate('left_date', '<', now()->toDateString());
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('project', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                            ->orWhere('project_code', 'like', "%{$search}%");
                    })->orWhereHas('employee', function ($e) use ($search) {
                        $e->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.project-team-members.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    if (! $row->employee) {
                        return '<span class="text-danger">Employee removed</span>';
                    }

                    return '<b class="tl-name-txt">' . e($row->employee->first_name . ' ' . $row->employee->last_name) . '</b><br><small>'
                        . e($row->employee->employee_code)
                        . ($row->employee->designation ? ' · ' . e($row->employee->designation->name) : '')
                        . '</small>';
                })
                ->addColumn('project_name', function ($row) {
                    if (! $row->project) {
                        return '<span class="text-danger">Project removed</span>';
                    }

                    return e($row->project->name) . '<br><small>' . e($row->project->project_code)
                        . ($row->project->client ? ' · ' . e($row->project->client->name) : '') . '</small>';
                })
                ->addColumn('role_badge', function ($row) {
                    $class = $row->team_role === 'lead' ? 'bg-primary' : 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . e($row->team_role_label) . '</span>';
                })
                ->addColumn('allocation_label', function ($row) {
                    return rtrim(rtrim(number_format($row->allocation_percent, 2), '0'), '.') . '%';
                })
                ->addColumn('membership', function ($row) {
                    $line = $row->joined_date->format('d M Y') . ' → '
                        . ($row->left_date ? $row->left_date->format('d M Y') : 'Ongoing');

                    $line .= $row->is_current
                        ? '<br><span class="badge bg-success">Current</span>'
                        : '<br><span class="badge bg-secondary">Past</span>';

                    return $line;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.project-team-members.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'project_name', 'role_badge', 'membership', 'action'])
                ->make(true);
        }

        return view('admin.project-team-members.index', $this->formData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.project-team-members.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProjectTeamMemberRequest $request)
    {
        DB::beginTransaction();

        try {
            $member = $this->projectTeamMemberService->create($request->validated());
            $member->load(['project', 'employee']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-team-members',
                'action' => 'create',
                'model' => 'ProjectTeamMember',
                'model_id' => $member->id,
                'description' => 'Team member "' . $this->memberLabel($member) . '" added',
                'new_data' => $member->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Team member added successfully.'
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
    public function edit(ProjectTeamMember $projectTeamMember)
    {
        return view('admin.project-team-members.edit', array_merge($this->formData(), [
            'projectTeamMember' => $projectTeamMember,
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectTeamMemberRequest $request, ProjectTeamMember $projectTeamMember)
    {
        DB::beginTransaction();

        try {
            $oldData = $projectTeamMember->toArray();
            $updatedMember = $this->projectTeamMemberService->update($projectTeamMember, $request->validated());
            $updatedMember->load(['project', 'employee']);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-team-members',
                'action' => 'update',
                'model' => 'ProjectTeamMember',
                'model_id' => $projectTeamMember->id,
                'description' => 'Team member "' . $this->memberLabel($updatedMember) . '" updated',
                'new_data' => $updatedMember->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.project-team-members.index'),
                'message' => 'Team member updated successfully.'
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
    public function destroy(ProjectTeamMember $projectTeamMember)
    {
        DB::beginTransaction();

        try {
            $projectTeamMember->load(['project', 'employee']);
            $oldData = $projectTeamMember->toArray();
            $label = $this->memberLabel($projectTeamMember);

            $this->projectTeamMemberService->delete($projectTeamMember);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'project-team-members',
                'action' => 'delete',
                'model' => 'ProjectTeamMember',
                'model_id' => $oldData['id'],
                'description' => 'Team member "' . $label . '" removed',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Team member removed successfully.'
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

        $model = ProjectTeamMember::find($id);
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
     * Readable "Employee on Project" label used in the activity log.
     */
    protected function memberLabel(ProjectTeamMember $member): string
    {
        $employee = $member->employee
            ? $member->employee->first_name . ' ' . $member->employee->last_name
            : 'Unknown employee';

        return $employee . ' on ' . ($member->project?->project_code ?? 'unknown project');
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
