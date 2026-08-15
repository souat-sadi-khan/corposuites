<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseInvoiceRequest;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseInvoiceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseInvoiceController extends Controller
{
    use ActivityLogger;

    protected $purchaseInvoiceService;

    public function __construct(PurchaseInvoiceService $purchaseInvoiceService)
    {
        $this->purchaseInvoiceService = $purchaseInvoiceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseInvoice::query()->with(['vendor', 'purchaseOrder'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by invoice status
            if ($request->invoice_status) {
                $query->where('invoice_status', $request->invoice_status);
            }

            // Filter by match status
            if ($request->match_status) {
                $query->where('match_status', $request->match_status);
            }

            // Filter by vendor
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.purchase-invoices.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('invoice_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->invoice_number . '</b><br><small>' . ($row->vendor->name ?? '-') . '</small>';
                })
                ->addColumn('po_number_label', function ($row) {
                    return $row->purchaseOrder->po_number ?? '-';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('invoice_date_formatted', function ($row) {
                    return $row->invoice_date ? $row->invoice_date->format('d M, Y') : '-';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('balance_due_formatted', function ($row) {
                    return number_format($row->balance_due, 2);
                })
                ->addColumn('match_status_badge', function ($row) {
                    $colors = [
                        'unmatched' => 'secondary',
                        'matched' => 'success',
                        'discrepancy' => 'danger',
                    ];
                    $color = $colors[$row->match_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->match_status) . '</span>';
                })
                ->addColumn('invoice_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'approved' => 'info',
                        'paid' => 'success',
                        'disputed' => 'warning',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->invoice_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->invoice_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.purchase-invoices.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'invoice_number', 'match_status_badge', 'invoice_status_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->get();

        return view('admin.purchase-invoices.index', compact('vendors'));
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

        return view('admin.purchase-invoices.create', compact('vendors', 'purchaseOrders', 'goodsReceipts', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseInvoiceRequest $request)
    {
        DB::beginTransaction();

        try {
            $purchaseInvoice = $this->purchaseInvoiceService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-invoices',
                'action' => 'create',
                'model' => 'PurchaseInvoice',
                'model_id' => $purchaseInvoice->id,
                'description' => 'Purchase Invoice "' . $purchaseInvoice->invoice_number . '" created',
                'new_data' => $purchaseInvoice->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase invoice created successfully.'
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
    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        $vendors = Vendor::active()->get();
        $purchaseOrders = PurchaseOrder::active()->get();
        $goodsReceipts = GoodsReceipt::active()->get();
        $products = Product::active()->get();
        $purchaseInvoice->load('items');

        return view('admin.purchase-invoices.edit', compact('purchaseInvoice', 'vendors', 'purchaseOrders', 'goodsReceipts', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseInvoice->load('items')->toArray();
            $updatedPurchaseInvoice = $this->purchaseInvoiceService->update($purchaseInvoice, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-invoices',
                'action' => 'update',
                'model' => 'PurchaseInvoice',
                'model_id' => $purchaseInvoice->id,
                'description' => 'Purchase Invoice "' . $purchaseInvoice->invoice_number . '" updated',
                'new_data' => $updatedPurchaseInvoice->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.purchase-invoices.index'),
                'message' => 'Purchase invoice updated successfully.'
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
    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        DB::beginTransaction();

        try {
            $oldData = $purchaseInvoice->load('items')->toArray();

            $this->purchaseInvoiceService->delete($purchaseInvoice);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'purchase-invoices',
                'action' => 'delete',
                'model' => 'PurchaseInvoice',
                'model_id' => $oldData['id'],
                'description' => 'Purchase Invoice "' . $oldData['invoice_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Purchase invoice deleted successfully.'
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

        $model = PurchaseInvoice::find($id);
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
