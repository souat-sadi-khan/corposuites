<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SlaPolicyRequest;
use App\Models\SlaPolicy;
use App\Services\SlaPolicyService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SlaPolicyController extends Controller
{
    use ActivityLogger;

    protected $slaPolicyService;

    public function __construct(SlaPolicyService $slaPolicyService)
    {
        $this->slaPolicyService = $slaPolicyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SlaPolicy::withCount('tickets');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // A fixed, meaningful order (low -> urgent) rather than id/name,
            // since there are only ever up to 4 rows and this reads as a
            // natural escalation ladder.
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')");

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.sla-policies.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name_col', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b>';
                })
                ->addColumn('priority_badge', function ($row) {
                    $map = [
                        'low' => 'bg-secondary',
                        'medium' => 'bg-info',
                        'high' => 'bg-warning',
                        'urgent' => 'bg-danger',
                    ];

                    return '<span class="badge ' . ($map[$row->priority] ?? 'bg-secondary') . '">' . e($row->priority_label) . '</span>';
                })
                ->addColumn('targets_col', function ($row) {
                    $response = rtrim(rtrim(number_format($row->response_time_hours, 2), '0'), '.');
                    $resolution = rtrim(rtrim(number_format($row->resolution_time_hours, 2), '0'), '.');

                    return 'Respond in ' . $response . 'h<br><small>Resolve in ' . $resolution . 'h</small>';
                })
                ->addColumn('tickets_count_label', function ($row) {
                    return (int) $row->tickets_count;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.sla-policies.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name_col', 'priority_badge', 'targets_col', 'action'])
                ->make(true);
        }

        return view('admin.sla-policies.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sla-policies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SlaPolicyRequest $request)
    {
        DB::beginTransaction();

        try {
            $slaPolicy = $this->slaPolicyService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sla-policies',
                'action' => 'create',
                'model' => 'SlaPolicy',
                'model_id' => $slaPolicy->id,
                'description' => 'SLA Policy "' . $slaPolicy->name . '" created',
                'new_data' => $slaPolicy->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'SLA policy created successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SlaPolicy $slaPolicy)
    {
        return view('admin.sla-policies.edit', compact('slaPolicy'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SlaPolicyRequest $request, SlaPolicy $slaPolicy)
    {
        DB::beginTransaction();

        try {
            $oldData = $slaPolicy->toArray();
            $updatedSlaPolicy = $this->slaPolicyService->update($slaPolicy, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sla-policies',
                'action' => 'update',
                'model' => 'SlaPolicy',
                'model_id' => $slaPolicy->id,
                'description' => 'SLA Policy "' . $updatedSlaPolicy->name . '" updated',
                'new_data' => $updatedSlaPolicy->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.sla-policies.index'),
                'message' => 'SLA policy updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SlaPolicy $slaPolicy)
    {
        DB::beginTransaction();

        try {
            $oldData = $slaPolicy->toArray();

            $this->slaPolicyService->delete($slaPolicy);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sla-policies',
                'action' => 'delete',
                'model' => 'SlaPolicy',
                'model_id' => $oldData['id'],
                'description' => 'SLA Policy "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'SLA policy deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
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

        $model = SlaPolicy::find($id);
        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.',
        ]);
    }
}
