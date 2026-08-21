<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApprovalDelegationRequest;
use App\Models\Admin;
use App\Models\ApprovalDelegation;
use App\Services\ApprovalDelegationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ApprovalDelegationController extends Controller
{
    use ActivityLogger;

    protected $approvalDelegationService;

    public function __construct(ApprovalDelegationService $approvalDelegationService)
    {
        $this->approvalDelegationService = $approvalDelegationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ApprovalDelegation::query()->with(['delegator', 'delegate']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                      ->orWhereHas('delegator', function ($dq) use ($search) {
                          $dq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('delegate', function ($dq) use ($search) {
                          $dq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.approval-delegations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('delegator_name', function ($row) {
                    return $row->delegator->name ?? '-';
                })
                ->addColumn('delegate_name', function ($row) {
                    return $row->delegate->name ?? '-';
                })
                ->addColumn('period', function ($row) {
                    $start = $row->starts_on ? $row->starts_on->format('d-m-Y') : '-';
                    $end = $row->ends_on ? $row->ends_on->format('d-m-Y') : '-';
                    $active = $row->status
                        && $row->starts_on && $row->ends_on
                        && $row->starts_on->lte(now()) && $row->ends_on->gte(now());
                    $live = $active ? ' <span class="badge bg-success-subtle text-success">Active now</span>' : '';
                    return $start . ' to ' . $end . $live;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.approval-delegations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'delegator_name', 'delegate_name', 'period', 'action'])
                ->make(true);
        }

        return view('admin.approval-delegations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $admins = Admin::orderBy('name')->get();

        return view('admin.approval-delegations.create', compact('admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ApprovalDelegationRequest $request)
    {
        DB::beginTransaction();

        try {
            $approvalDelegation = $this->approvalDelegationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'approval-delegations',
                'action' => 'create',
                'model' => 'ApprovalDelegation',
                'model_id' => $approvalDelegation->id,
                'description' => 'Approval delegation created (delegator #' . $approvalDelegation->delegator_admin_id . ' -> delegate #' . $approvalDelegation->delegate_admin_id . ')',
                'new_data' => $approvalDelegation->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Approval delegation created successfully.'
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
    public function edit(ApprovalDelegation $approvalDelegation)
    {
        $admins = Admin::orderBy('name')->get();

        return view('admin.approval-delegations.edit', compact('approvalDelegation', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ApprovalDelegationRequest $request, ApprovalDelegation $approvalDelegation)
    {
        DB::beginTransaction();

        try {
            $oldData = $approvalDelegation->toArray();
            $updated = $this->approvalDelegationService->update($approvalDelegation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'approval-delegations',
                'action' => 'update',
                'model' => 'ApprovalDelegation',
                'model_id' => $approvalDelegation->id,
                'description' => 'Approval delegation updated (delegator #' . $updated->delegator_admin_id . ' -> delegate #' . $updated->delegate_admin_id . ')',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.approval-delegations.index'),
                'message' => 'Approval delegation updated successfully.'
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
    public function destroy(ApprovalDelegation $approvalDelegation)
    {
        DB::beginTransaction();

        try {
            $oldData = $approvalDelegation->toArray();

            $this->approvalDelegationService->delete($approvalDelegation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'approval-delegations',
                'action' => 'delete',
                'model' => 'ApprovalDelegation',
                'model_id' => $oldData['id'],
                'description' => 'Approval delegation deleted (delegator #' . $oldData['delegator_admin_id'] . ' -> delegate #' . $oldData['delegate_admin_id'] . ')',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Approval delegation deleted successfully.'
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

        $model = ApprovalDelegation::find($id);
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
