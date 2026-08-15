<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\StockAdjustmentService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockAdjustmentController extends Controller
{
    use ActivityLogger;

    protected $stockAdjustmentService;

    public function __construct(StockAdjustmentService $stockAdjustmentService)
    {
        $this->stockAdjustmentService = $stockAdjustmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockAdjustment::query()->with(['warehouse', 'warehouseLocation'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by adjustment status
            if ($request->adjustment_status) {
                $query->where('adjustment_status', $request->adjustment_status);
            }

            // Filter by warehouse
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('adjustment_number', 'like', "%{$search}%")
                        ->orWhereHas('warehouse', function ($wq) use ($search) {
                            $wq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.stock-adjustments.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('adjustment_number', function ($row) {
                    $subtitle = $row->warehouse->name ?? '-';
                    if ($row->warehouseLocation) {
                        $subtitle .= ' · ' . $row->warehouseLocation->name;
                    }
                    return '<b class="tl-name-txt">' . $row->adjustment_number . '</b><br><small>' . $subtitle . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('adjustment_date_formatted', function ($row) {
                    return $row->adjustment_date ? $row->adjustment_date->format('d M, Y') : '-';
                })
                ->addColumn('adjustment_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->adjustment_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->adjustment_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.stock-adjustments.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'adjustment_number', 'adjustment_status_badge', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::active()->get();

        return view('admin.stock-adjustments.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();

        return view('admin.stock-adjustments.create', compact('warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockAdjustmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $stockAdjustment = $this->stockAdjustmentService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-adjustments',
                'action' => 'create',
                'model' => 'StockAdjustment',
                'model_id' => $stockAdjustment->id,
                'description' => 'Stock Adjustment "' . $stockAdjustment->adjustment_number . '" created',
                'new_data' => $stockAdjustment->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock adjustment created successfully.'
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
    public function edit(StockAdjustment $stockAdjustment)
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();
        $stockAdjustment->load('items');

        return view('admin.stock-adjustments.edit', compact('stockAdjustment', 'warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StockAdjustmentRequest $request, StockAdjustment $stockAdjustment)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockAdjustment->load('items')->toArray();
            $updatedStockAdjustment = $this->stockAdjustmentService->update($stockAdjustment, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-adjustments',
                'action' => 'update',
                'model' => 'StockAdjustment',
                'model_id' => $stockAdjustment->id,
                'description' => 'Stock Adjustment "' . $stockAdjustment->adjustment_number . '" updated',
                'new_data' => $updatedStockAdjustment->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.stock-adjustments.index'),
                'message' => 'Stock adjustment updated successfully.'
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
    public function destroy(StockAdjustment $stockAdjustment)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockAdjustment->load('items')->toArray();

            $this->stockAdjustmentService->delete($stockAdjustment);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-adjustments',
                'action' => 'delete',
                'model' => 'StockAdjustment',
                'model_id' => $oldData['id'],
                'description' => 'Stock Adjustment "' . $oldData['adjustment_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock adjustment deleted successfully.'
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

        $model = StockAdjustment::find($id);
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
