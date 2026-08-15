<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\SupplierQuotation;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    use ActivityLogger;

    protected $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseOrder::query()->with(['vendor', 'purchaseRequest', 'rfq', 'supplierQuotation'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by order status
            if ($request->order_status) {
                $query->where('order_status', $request->order_status);
            }

            // Filter by vendor
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.purchase-orders.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('po_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->po_number . '</b><br><small>' . ($row->vendor->name ?? '-') . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('order_date_formatted', function ($row) {
                    return $row->order_date ? $row->order_date->format('d M, Y') : '-';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('order_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'confirmed' => 'info',
                        'partially_received' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->order_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $row->order_status)) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.purchase-orders.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'po_number', 'order_status_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->get();

        return view('admin.purchase-orders.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = Vendor::active()->get();
        $purchaseRequests = PurchaseRequest::active()->get();
        $rfqs = Rfq::active()->get();
        $supplierQuotations = SupplierQuotation::active()->get();
        $products = Product::active()->get();

        return view('admin.purchase-orders.create', compact('vendors', 'purchaseRequests', 'rfqs', 'supplierQuotations', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $purchaseOrder = $this->purchaseOrderService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-orders',
                'action' => 'create',
                'model' => 'PurchaseOrder',
                'model_id' => $purchaseOrder->id,
                'description' => 'Purchase Order "' . $purchaseOrder->po_number . '" created',
                'new_data' => $purchaseOrder->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase order created successfully.'
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
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $vendors = Vendor::active()->get();
        $purchaseRequests = PurchaseRequest::active()->get();
        $rfqs = Rfq::active()->get();
        $supplierQuotations = SupplierQuotation::active()->get();
        $products = Product::active()->get();
        $purchaseOrder->load('items');

        return view('admin.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'purchaseRequests', 'rfqs', 'supplierQuotations', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseOrder->load('items')->toArray();
            $updatedPurchaseOrder = $this->purchaseOrderService->update($purchaseOrder, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-orders',
                'action' => 'update',
                'model' => 'PurchaseOrder',
                'model_id' => $purchaseOrder->id,
                'description' => 'Purchase Order "' . $purchaseOrder->po_number . '" updated',
                'new_data' => $updatedPurchaseOrder->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.purchase-orders.index'),
                'message' => 'Purchase order updated successfully.'
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
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseOrder->load('items')->toArray();

            $this->purchaseOrderService->delete($purchaseOrder);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-orders',
                'action' => 'delete',
                'model' => 'PurchaseOrder',
                'model_id' => $oldData['id'],
                'description' => 'Purchase Order "' . $oldData['po_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase order deleted successfully.'
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

        $model = PurchaseOrder::find($id);
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
