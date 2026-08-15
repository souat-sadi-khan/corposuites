<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockTransferRequest;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\StockTransferService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockTransferController extends Controller
{
    use ActivityLogger;

    protected $stockTransferService;

    public function __construct(StockTransferService $stockTransferService)
    {
        $this->stockTransferService = $stockTransferService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockTransfer::query()->with(['fromWarehouse', 'toWarehouse'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by transfer status
            if ($request->transfer_status) {
                $query->where('transfer_status', $request->transfer_status);
            }

            // Filter by warehouse (either side of the transfer)
            if ($request->warehouse_id) {
                $warehouseId = $request->warehouse_id;
                $query->where(function ($q) use ($warehouseId) {
                    $q->where('from_warehouse_id', $warehouseId)
                      ->orWhere('to_warehouse_id', $warehouseId);
                });
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transfer_number', 'like', "%{$search}%")
                        ->orWhereHas('fromWarehouse', function ($wq) use ($search) {
                            $wq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('toWarehouse', function ($wq) use ($search) {
                            $wq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.stock-transfers.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('transfer_number', function ($row) {
                    $subtitle = ($row->fromWarehouse->name ?? '-') . ' → ' . ($row->toWarehouse->name ?? '-');
                    return '<b class="tl-name-txt">' . $row->transfer_number . '</b><br><small>' . $subtitle . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('transfer_date_formatted', function ($row) {
                    return $row->transfer_date ? $row->transfer_date->format('d M, Y') : '-';
                })
                ->addColumn('transfer_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'in_transit' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->transfer_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $row->transfer_status)) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.stock-transfers.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'transfer_number', 'transfer_status_badge', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::active()->get();

        return view('admin.stock-transfers.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();

        return view('admin.stock-transfers.create', compact('warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockTransferRequest $request)
    {
        DB::beginTransaction();

        try {
            $stockTransfer = $this->stockTransferService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-transfers',
                'action' => 'create',
                'model' => 'StockTransfer',
                'model_id' => $stockTransfer->id,
                'description' => 'Stock Transfer "' . $stockTransfer->transfer_number . '" created',
                'new_data' => $stockTransfer->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock transfer created successfully.'
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
    public function edit(StockTransfer $stockTransfer)
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();
        $stockTransfer->load('items');

        return view('admin.stock-transfers.edit', compact('stockTransfer', 'warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StockTransferRequest $request, StockTransfer $stockTransfer)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockTransfer->load('items')->toArray();
            $updatedStockTransfer = $this->stockTransferService->update($stockTransfer, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-transfers',
                'action' => 'update',
                'model' => 'StockTransfer',
                'model_id' => $stockTransfer->id,
                'description' => 'Stock Transfer "' . $stockTransfer->transfer_number . '" updated',
                'new_data' => $updatedStockTransfer->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.stock-transfers.index'),
                'message' => 'Stock transfer updated successfully.'
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
    public function destroy(StockTransfer $stockTransfer)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockTransfer->load('items')->toArray();

            $this->stockTransferService->delete($stockTransfer);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-transfers',
                'action' => 'delete',
                'model' => 'StockTransfer',
                'model_id' => $oldData['id'],
                'description' => 'Stock Transfer "' . $oldData['transfer_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock transfer deleted successfully.'
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

        $model = StockTransfer::find($id);
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
