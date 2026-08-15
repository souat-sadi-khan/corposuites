<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseRequestRequest;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\WorkflowDefinition;
use App\Services\PurchaseRequestService;
use App\Services\WorkflowEngineService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseRequestController extends Controller
{
    use ActivityLogger;

    protected $purchaseRequestService;

    public function __construct(PurchaseRequestService $purchaseRequestService)
    {
        $this->purchaseRequestService = $purchaseRequestService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseRequest::query()->with(['requestedBy', 'department'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by request status
            if ($request->request_status) {
                $query->where('request_status', $request->request_status);
            }

            // Filter by department
            if ($request->department_id) {
                $query->where('department_id', $request->department_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                        ->orWhereHas('requestedBy', function ($aq) use ($search) {
                            $aq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.purchase-requests.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('request_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->request_number . '</b><br><small>' . ($row->requestedBy->name ?? 'Unassigned') . '</small>';
                })
                ->addColumn('department_name', function ($row) {
                    return $row->department->name ?? '-';
                })
                ->addColumn('request_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'dark',
                    ];
                    $color = $colors[$row->request_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->request_status) . '</span>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('required_date_formatted', function ($row) {
                    return $row->required_date ? $row->required_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.purchase-requests.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'request_number', 'request_status_badge', 'action'])
                ->make(true);
        }

        $departments = Department::active()->get();

        return view('admin.purchase-requests.index', compact('departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $admins = Admin::all();
        $departments = Department::active()->get();
        $products = Product::active()->get();

        return view('admin.purchase-requests.create', compact('admins', 'departments', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseRequestRequest $request)
    {
        DB::beginTransaction();

        try {
            $purchaseRequest = $this->purchaseRequestService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-requests',
                'action' => 'create',
                'model' => 'PurchaseRequest',
                'model_id' => $purchaseRequest->id,
                'description' => 'Purchase Request "' . $purchaseRequest->request_number . '" created',
                'new_data' => $purchaseRequest->load('items')->toArray(),
                'old_data' => null
            ]);

            // If an active Workflow Engine definition exists for PurchaseRequest, kick off
            // an approval instance. No definitions are seeded yet, so this is a no-op today.
            if (WorkflowDefinition::where('approvable_type', PurchaseRequest::class)->where('status', true)->exists()) {
                app(WorkflowEngineService::class)->start($purchaseRequest, auth()->guard('admin')->id());
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase request created successfully.'
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
    public function edit(PurchaseRequest $purchaseRequest)
    {
        $admins = Admin::all();
        $departments = Department::active()->get();
        $products = Product::active()->get();
        $purchaseRequest->load('items');

        return view('admin.purchase-requests.edit', compact('purchaseRequest', 'admins', 'departments', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseRequestRequest $request, PurchaseRequest $purchaseRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseRequest->load('items')->toArray();
            $updatedPurchaseRequest = $this->purchaseRequestService->update($purchaseRequest, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-requests',
                'action' => 'update',
                'model' => 'PurchaseRequest',
                'model_id' => $purchaseRequest->id,
                'description' => 'Purchase Request "' . $purchaseRequest->request_number . '" updated',
                'new_data' => $updatedPurchaseRequest->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.purchase-requests.index'),
                'message' => 'Purchase request updated successfully.'
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
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseRequest->load('items')->toArray();

            $this->purchaseRequestService->delete($purchaseRequest);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-requests',
                'action' => 'delete',
                'model' => 'PurchaseRequest',
                'model_id' => $oldData['id'],
                'description' => 'Purchase Request "' . $oldData['request_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase request deleted successfully.'
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
     * Approve the request.
     */
    public function approve(PurchaseRequest $purchaseRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseRequest->toArray();

            // Fallback-safe: only route through the Workflow Engine when an active
            // WorkflowDefinition exists for PurchaseRequest. Today none exists, so this
            // always takes the else branch — plain service call.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', PurchaseRequest::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $purchaseRequest->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'approved');
                } else {
                    $this->purchaseRequestService->approve($purchaseRequest);
                }
            } else {
                $this->purchaseRequestService->approve($purchaseRequest);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-requests',
                'action' => 'approve',
                'model' => 'PurchaseRequest',
                'model_id' => $purchaseRequest->id,
                'description' => 'Purchase Request "' . $purchaseRequest->request_number . '" approved',
                'new_data' => $purchaseRequest->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase request approved.'
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
     * Reject the request.
     */
    public function reject(PurchaseRequest $purchaseRequest)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseRequest->toArray();

            // Fallback-safe: same pattern as approve() above.
            $hasWorkflow = WorkflowDefinition::where('approvable_type', PurchaseRequest::class)->where('status', true)->exists();

            if ($hasWorkflow) {
                $instance = $purchaseRequest->workflowInstance;

                if ($instance) {
                    app(WorkflowEngineService::class)->act($instance, auth()->guard('admin')->id(), 'rejected');
                } else {
                    $this->purchaseRequestService->reject($purchaseRequest);
                }
            } else {
                $this->purchaseRequestService->reject($purchaseRequest);
            }

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-requests',
                'action' => 'reject',
                'model' => 'PurchaseRequest',
                'model_id' => $purchaseRequest->id,
                'description' => 'Purchase Request "' . $purchaseRequest->request_number . '" rejected',
                'new_data' => $purchaseRequest->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase request rejected.'
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

        $model = PurchaseRequest::find($id);
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
