<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RfqRequest;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\Vendor;
use App\Services\RfqService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RfqController extends Controller
{
    use ActivityLogger;

    protected $rfqService;

    public function __construct(RfqService $rfqService)
    {
        $this->rfqService = $rfqService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Rfq::query()->with(['purchaseRequest'])->withCount(['items', 'rfqVendors']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by RFQ status
            if ($request->rfq_status) {
                $query->where('rfq_status', $request->rfq_status);
            }

            // Filter by source purchase request
            if ($request->purchase_request_id) {
                $query->where('purchase_request_id', $request->purchase_request_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('rfq_number', 'like', "%{$search}%")
                        ->orWhereHas('purchaseRequest', function ($pq) use ($search) {
                            $pq->where('request_number', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.rfqs.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('rfq_number', function ($row) {
                    $subtitle = $row->purchaseRequest->request_number ?? 'No source request';
                    return '<b class="tl-name-txt">' . $row->rfq_number . '</b><br><small>' . $subtitle . '</small>';
                })
                ->addColumn('rfq_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'sent' => 'info',
                        'closed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->rfq_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->rfq_status) . '</span>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('vendors_count_label', function ($row) {
                    return $row->rfq_vendors_count . ' vendor' . ($row->rfq_vendors_count == 1 ? '' : 's');
                })
                ->addColumn('rfq_date_formatted', function ($row) {
                    return $row->rfq_date ? $row->rfq_date->format('d M, Y') : '-';
                })
                ->addColumn('due_date_formatted', function ($row) {
                    return $row->due_date ? $row->due_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.rfqs.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'rfq_number', 'rfq_status_badge', 'action'])
                ->make(true);
        }

        $purchaseRequests = PurchaseRequest::active()->get();

        return view('admin.rfqs.index', compact('purchaseRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $purchaseRequests = PurchaseRequest::active()->get();
        $products = Product::active()->get();
        $vendors = Vendor::active()->get();

        return view('admin.rfqs.create', compact('purchaseRequests', 'products', 'vendors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RfqRequest $request)
    {
        DB::beginTransaction();

        try {
            $rfq = $this->rfqService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'rfqs',
                'action' => 'create',
                'model' => 'Rfq',
                'model_id' => $rfq->id,
                'description' => 'RFQ "' . $rfq->rfq_number . '" created',
                'new_data' => $rfq->load('items', 'rfqVendors')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'RFQ created successfully.'
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
    public function edit(Rfq $rfq)
    {
        $purchaseRequests = PurchaseRequest::active()->get();
        $products = Product::active()->get();
        $vendors = Vendor::active()->get();
        $rfq->load('items', 'rfqVendors');

        return view('admin.rfqs.edit', compact('rfq', 'purchaseRequests', 'products', 'vendors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RfqRequest $request, Rfq $rfq)
    {
        DB::beginTransaction();

        try {
            $oldData = $rfq->load('items', 'rfqVendors')->toArray();
            $updatedRfq = $this->rfqService->update($rfq, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'rfqs',
                'action' => 'update',
                'model' => 'Rfq',
                'model_id' => $rfq->id,
                'description' => 'RFQ "' . $rfq->rfq_number . '" updated',
                'new_data' => $updatedRfq->load('items', 'rfqVendors')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.rfqs.index'),
                'message' => 'RFQ updated successfully.'
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
    public function destroy(Rfq $rfq)
    {
        DB::beginTransaction();

        try {
            $oldData = $rfq->load('items', 'rfqVendors')->toArray();

            $this->rfqService->delete($rfq);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'rfqs',
                'action' => 'delete',
                'model' => 'Rfq',
                'model_id' => $oldData['id'],
                'description' => 'RFQ "' . $oldData['rfq_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'RFQ deleted successfully.'
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

        $model = Rfq::find($id);
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
