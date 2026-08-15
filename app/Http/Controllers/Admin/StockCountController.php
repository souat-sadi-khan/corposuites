<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockCountRequest;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\StockCountService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockCountController extends Controller
{
    use ActivityLogger;

    protected $stockCountService;

    public function __construct(StockCountService $stockCountService)
    {
        $this->stockCountService = $stockCountService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockCount::query()->with(['warehouse', 'warehouseLocation'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by count status
            if ($request->count_status) {
                $query->where('count_status', $request->count_status);
            }

            // Filter by warehouse
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('count_number', 'like', "%{$search}%")
                        ->orWhereHas('warehouse', function ($wq) use ($search) {
                            $wq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.stock-counts.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('count_number', function ($row) {
                    $subtitle = $row->warehouse->name ?? '-';
                    if ($row->warehouseLocation) {
                        $subtitle .= ' · ' . $row->warehouseLocation->name;
                    }
                    return '<b class="tl-name-txt">' . $row->count_number . '</b><br><small>' . $subtitle . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('count_date_formatted', function ($row) {
                    return $row->count_date ? $row->count_date->format('d M, Y') : '-';
                })
                ->addColumn('count_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->count_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->count_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.stock-counts.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'count_number', 'count_status_badge', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::active()->get();

        return view('admin.stock-counts.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();

        return view('admin.stock-counts.create', compact('warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockCountRequest $request)
    {
        DB::beginTransaction();

        try {
            $stockCount = $this->stockCountService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-counts',
                'action' => 'create',
                'model' => 'StockCount',
                'model_id' => $stockCount->id,
                'description' => 'Stock Count "' . $stockCount->count_number . '" created',
                'new_data' => $stockCount->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock count created successfully.'
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
    public function edit(StockCount $stockCount)
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();
        $stockCount->load('items');

        return view('admin.stock-counts.edit', compact('stockCount', 'warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StockCountRequest $request, StockCount $stockCount)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockCount->load('items')->toArray();
            $updatedStockCount = $this->stockCountService->update($stockCount, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-counts',
                'action' => 'update',
                'model' => 'StockCount',
                'model_id' => $stockCount->id,
                'description' => 'Stock Count "' . $stockCount->count_number . '" updated',
                'new_data' => $updatedStockCount->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.stock-counts.index'),
                'message' => 'Stock count updated successfully.'
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
    public function destroy(StockCount $stockCount)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockCount->load('items')->toArray();

            $this->stockCountService->delete($stockCount);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-counts',
                'action' => 'delete',
                'model' => 'StockCount',
                'model_id' => $oldData['id'],
                'description' => 'Stock Count "' . $oldData['count_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock count deleted successfully.'
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

        $model = StockCount::find($id);
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
