<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GoodsReceiptRequest;
use App\Models\Admin;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class GoodsReceiptController extends Controller
{
    use ActivityLogger;

    protected $goodsReceiptService;

    public function __construct(GoodsReceiptService $goodsReceiptService)
    {
        $this->goodsReceiptService = $goodsReceiptService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = GoodsReceipt::query()->with(['purchaseOrder.vendor', 'receivedBy'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by receipt status
            if ($request->receipt_status) {
                $query->where('receipt_status', $request->receipt_status);
            }

            // Filter by purchase order
            if ($request->purchase_order_id) {
                $query->where('purchase_order_id', $request->purchase_order_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', function ($pq) use ($search) {
                            $pq->where('po_number', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.goods-receipts.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('receipt_number', function ($row) {
                    $subtitle = ($row->purchaseOrder->po_number ?? '-') . ' · ' . ($row->purchaseOrder->vendor->name ?? '-');
                    return '<b class="tl-name-txt">' . $row->receipt_number . '</b><br><small>' . $subtitle . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('received_date_formatted', function ($row) {
                    return $row->received_date ? $row->received_date->format('d M, Y') : '-';
                })
                ->addColumn('receipt_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'received' => 'info',
                        'inspected' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->receipt_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->receipt_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.goods-receipts.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'receipt_number', 'receipt_status_badge', 'action'])
                ->make(true);
        }

        $purchaseOrders = PurchaseOrder::active()->get();

        return view('admin.goods-receipts.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $purchaseOrders = PurchaseOrder::active()->get();
        $admins = Admin::all();
        $products = Product::active()->get();

        return view('admin.goods-receipts.create', compact('purchaseOrders', 'admins', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GoodsReceiptRequest $request)
    {
        DB::beginTransaction();

        try {
            $goodsReceipt = $this->goodsReceiptService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'goods-receipts',
                'action' => 'create',
                'model' => 'GoodsReceipt',
                'model_id' => $goodsReceipt->id,
                'description' => 'Goods Receipt "' . $goodsReceipt->receipt_number . '" created',
                'new_data' => $goodsReceipt->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt created successfully.'
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
    public function edit(GoodsReceipt $goodsReceipt)
    {
        $purchaseOrders = PurchaseOrder::active()->get();
        $admins = Admin::all();
        $products = Product::active()->get();
        $goodsReceipt->load('items');

        return view('admin.goods-receipts.edit', compact('goodsReceipt', 'purchaseOrders', 'admins', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GoodsReceiptRequest $request, GoodsReceipt $goodsReceipt)
    {
        DB::beginTransaction();

        try {
            $oldData = $goodsReceipt->load('items')->toArray();
            $updatedGoodsReceipt = $this->goodsReceiptService->update($goodsReceipt, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'goods-receipts',
                'action' => 'update',
                'model' => 'GoodsReceipt',
                'model_id' => $goodsReceipt->id,
                'description' => 'Goods Receipt "' . $goodsReceipt->receipt_number . '" updated',
                'new_data' => $updatedGoodsReceipt->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.goods-receipts.index'),
                'message' => 'Goods receipt updated successfully.'
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
    public function destroy(GoodsReceipt $goodsReceipt)
    {
        DB::beginTransaction();

        try {
            $oldData = $goodsReceipt->load('items')->toArray();

            $this->goodsReceiptService->delete($goodsReceipt);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'goods-receipts',
                'action' => 'delete',
                'model' => 'GoodsReceipt',
                'model_id' => $oldData['id'],
                'description' => 'Goods Receipt "' . $oldData['receipt_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Goods receipt deleted successfully.'
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

        $model = GoodsReceipt::find($id);
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
