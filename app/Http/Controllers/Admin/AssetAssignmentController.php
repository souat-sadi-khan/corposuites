<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetAssignmentRequest;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Services\AssetAssignmentService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetAssignmentController extends Controller
{
    use ActivityLogger;

    protected $assetAssignmentService;

    public function __construct(AssetAssignmentService $assetAssignmentService)
    {
        $this->assetAssignmentService = $assetAssignmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetAssignment::query()->with(['asset', 'employee']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by assignment state
            if ($request->assignment_status) {
                $query->where('assignment_status', $request->assignment_status);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Overdue is computed from dates, not a stored flag, so it is
            // expressed here as a date comparison on still-open assignments.
            if ($request->overdue) {
                $query->where('assignment_status', 'assigned')
                    ->whereNotNull('expected_return_date')
                    ->whereDate('expected_return_date', '<', now()->toDateString());
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('asset', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('asset_code', 'like', "%{$search}%");
                    })->orWhereHas('employee', function ($sub) use ($search) {
                        $sub->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('assigned_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-assignments.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('asset_name', function ($row) {
                    if (! $row->asset) {
                        return '<span class="text-danger">Asset removed</span>';
                    }

                    return '<b class="tl-name-txt">' . e($row->asset->name) . '</b><br><small>' . e($row->asset->asset_code) . '</small>';
                })
                ->addColumn('employee_name', function ($row) {
                    if (! $row->employee) {
                        return '<span class="text-danger">Employee removed</span>';
                    }

                    return e(trim($row->employee->first_name . ' ' . $row->employee->last_name)) . '<br><small>' . e($row->employee->employee_code) . '</small>';
                })
                ->addColumn('assigned_date_formatted', function ($row) {
                    return $row->assigned_date->format('d M, Y');
                })
                ->addColumn('return_info', function ($row) {
                    if ($row->returned_date) {
                        $condition = $row->condition_on_return ? '<br><small>Returned ' . ucfirst($row->condition_on_return) . '</small>' : '';

                        return $row->returned_date->format('d M, Y') . $condition;
                    }

                    if (! $row->expected_return_date) {
                        return '<span class="text-muted">Open-ended</span>';
                    }

                    $due = $row->expected_return_date->format('d M, Y');

                    return $row->is_overdue
                        ? '<span class="text-danger">Due ' . $due . '</span><br><small class="text-danger">Overdue</small>'
                        : 'Due ' . $due;
                })
                ->addColumn('assignment_status_badge', function ($row) {
                    $map = [
                        'assigned' => 'bg-success',
                        'returned' => 'bg-secondary',
                        'cancelled' => 'bg-danger',
                    ];
                    $class = $map[$row->assignment_status] ?? 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . ucfirst($row->assignment_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-assignments.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'asset_name', 'employee_name', 'return_info', 'assignment_status_badge', 'action'])
                ->make(true);
        }

        $employees = Employee::active()->orderBy('first_name')->get();

        return view('admin.asset-assignments.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-assignments.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetAssignmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $assignment = $this->assetAssignmentService->create($request->validated());
            $assignment->load('asset', 'employee');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-assignments',
                'action' => 'create',
                'model' => 'AssetAssignment',
                'model_id' => $assignment->id,
                'description' => 'Asset "' . ($assignment->asset->asset_code ?? $assignment->asset_id) . '" assigned to ' . ($assignment->employee->employee_code ?? $assignment->employee_id),
                'new_data' => $assignment->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset assigned successfully.'
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
    public function edit(AssetAssignment $assetAssignment)
    {
        return view('admin.asset-assignments.edit', array_merge(
            $this->formData($assetAssignment),
            ['assetAssignment' => $assetAssignment]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetAssignmentRequest $request, AssetAssignment $assetAssignment)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetAssignment->toArray();
            $updated = $this->assetAssignmentService->update($assetAssignment, $request->validated());
            $updated->load('asset', 'employee');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-assignments',
                'action' => 'update',
                'model' => 'AssetAssignment',
                'model_id' => $assetAssignment->id,
                'description' => 'Assignment for asset "' . ($updated->asset->asset_code ?? $updated->asset_id) . '" updated (' . $updated->assignment_status . ')',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-assignments.index'),
                'message' => 'Assignment updated successfully.'
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
    public function destroy(AssetAssignment $assetAssignment)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetAssignment->toArray();

            $this->assetAssignmentService->delete($assetAssignment);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-assignments',
                'action' => 'delete',
                'model' => 'AssetAssignment',
                'model_id' => $oldData['id'],
                'description' => 'Assignment deleted for asset id ' . $oldData['asset_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Assignment deleted successfully.'
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

        $model = AssetAssignment::find($id);
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
     * Dropdown data shared by create/edit. Assets currently out with
     * someone are excluded so the UI cannot offer a choice the Form
     * Request would reject; on edit, this record's own asset is added back
     * so the current selection is never lost — the same picker technique
     * `AssetPurchaseController`/`DeliveryNoteController` use.
     */
    protected function formData(?AssetAssignment $assetAssignment = null): array
    {
        $assets = Asset::active()
            ->where('asset_status', '!=', 'disposed')
            ->whereDoesntHave('assetAssignments', function ($q) {
                $q->where('assignment_status', 'assigned');
            })
            ->when($assetAssignment, fn ($q) => $q->orWhere('id', $assetAssignment->asset_id))
            ->orderBy('asset_code')
            ->get();

        return [
            'assets' => $assets,
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
