<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseReturnRequest;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Vendor;
use App\Services\PurchaseReturnService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseReturnController extends Controller
{
    use ActivityLogger;

    protected $purchaseReturnService;

    public function __construct(PurchaseReturnService $purchaseReturnService)
    {
        $this->purchaseReturnService = $purchaseReturnService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseReturn::query()->with(['vendor', 'purchaseOrder', 'goodsReceipt'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by return status
            if ($request->return_status) {
                $query->where('return_status', $request->return_status);
            }

            // Filter by vendor
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.purchase-returns.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('return_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->return_number . '</b><br><small>' . ($row->vendor->name ?? '-') . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('return_date_formatted', function ($row) {
                    return $row->return_date ? $row->return_date->format('d M, Y') : '-';
                })
                ->addColumn('return_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'approved' => 'info',
                        'shipped' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->return_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->return_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.purchase-returns.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'return_number', 'return_status_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->get();

        return view('admin.purchase-returns.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = Vendor::active()->get();
        $purchaseOrders = PurchaseOrder::active()->get();
        $goodsReceipts = GoodsReceipt::active()->get();
        $products = Product::active()->get();

        return view('admin.purchase-returns.create', compact('vendors', 'purchaseOrders', 'goodsReceipts', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseReturnRequest $request)
    {
        DB::beginTransaction();

        try {
            $purchaseReturn = $this->purchaseReturnService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-returns',
                'action' => 'create',
                'model' => 'PurchaseReturn',
                'model_id' => $purchaseReturn->id,
                'description' => 'Purchase Return "' . $purchaseReturn->return_number . '" created',
                'new_data' => $purchaseReturn->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase return created successfully.'
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
    public function edit(PurchaseReturn $purchaseReturn)
    {
        $vendors = Vendor::active()->get();
        $purchaseOrders = PurchaseOrder::active()->get();
        $goodsReceipts = GoodsReceipt::active()->get();
        $products = Product::active()->get();
        $purchaseReturn->load('items');

        return view('admin.purchase-returns.edit', compact('purchaseReturn', 'vendors', 'purchaseOrders', 'goodsReceipts', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseReturnRequest $request, PurchaseReturn $purchaseReturn)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseReturn->load('items')->toArray();
            $updatedPurchaseReturn = $this->purchaseReturnService->update($purchaseReturn, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-returns',
                'action' => 'update',
                'model' => 'PurchaseReturn',
                'model_id' => $purchaseReturn->id,
                'description' => 'Purchase Return "' . $purchaseReturn->return_number . '" updated',
                'new_data' => $updatedPurchaseReturn->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.purchase-returns.index'),
                'message' => 'Purchase return updated successfully.'
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
    public function destroy(PurchaseReturn $purchaseReturn)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseReturn->load('items')->toArray();

            $this->purchaseReturnService->delete($purchaseReturn);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-returns',
                'action' => 'delete',
                'model' => 'PurchaseReturn',
                'model_id' => $oldData['id'],
                'description' => 'Purchase Return "' . $oldData['return_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase return deleted successfully.'
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

        $model = PurchaseReturn::find($id);
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
