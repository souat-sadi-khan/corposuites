<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockEntryRequest;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\StockEntryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockEntryController extends Controller
{
    use ActivityLogger;

    protected $stockEntryService;

    public function __construct(StockEntryService $stockEntryService)
    {
        $this->stockEntryService = $stockEntryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = StockEntry::query()->with(['warehouse', 'warehouseLocation'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by entry status
            if ($request->entry_status) {
                $query->where('entry_status', $request->entry_status);
            }

            // Filter by warehouse
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('entry_number', 'like', "%{$search}%")
                        ->orWhereHas('warehouse', function ($wq) use ($search) {
                            $wq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.stock-entries.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('entry_number', function ($row) {
                    $subtitle = $row->warehouse->name ?? '-';
                    if ($row->warehouseLocation) {
                        $subtitle .= ' · ' . $row->warehouseLocation->name;
                    }
                    return '<b class="tl-name-txt">' . $row->entry_number . '</b><br><small>' . $subtitle . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('entry_date_formatted', function ($row) {
                    return $row->entry_date ? $row->entry_date->format('d M, Y') : '-';
                })
                ->addColumn('entry_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->entry_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->entry_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.stock-entries.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'entry_number', 'entry_status_badge', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::active()->get();

        return view('admin.stock-entries.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $goodsReceipts = GoodsReceipt::active()->get();
        $products = Product::active()->get();

        return view('admin.stock-entries.create', compact('warehouses', 'warehouseLocations', 'goodsReceipts', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockEntryRequest $request)
    {
        DB::beginTransaction();

        try {
            $stockEntry = $this->stockEntryService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-entries',
                'action' => 'create',
                'model' => 'StockEntry',
                'model_id' => $stockEntry->id,
                'description' => 'Stock Entry "' . $stockEntry->entry_number . '" created',
                'new_data' => $stockEntry->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock entry created successfully.'
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
    public function edit(StockEntry $stockEntry)
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $goodsReceipts = GoodsReceipt::active()->get();
        $products = Product::active()->get();
        $stockEntry->load('items');

        return view('admin.stock-entries.edit', compact('stockEntry', 'warehouses', 'warehouseLocations', 'goodsReceipts', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StockEntryRequest $request, StockEntry $stockEntry)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockEntry->load('items')->toArray();
            $updatedStockEntry = $this->stockEntryService->update($stockEntry, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-entries',
                'action' => 'update',
                'model' => 'StockEntry',
                'model_id' => $stockEntry->id,
                'description' => 'Stock Entry "' . $stockEntry->entry_number . '" updated',
                'new_data' => $updatedStockEntry->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.stock-entries.index'),
                'message' => 'Stock entry updated successfully.'
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
    public function destroy(StockEntry $stockEntry)
    {
        DB::beginTransaction();

        try {
            $oldData = $stockEntry->load('items')->toArray();

            $this->stockEntryService->delete($stockEntry);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'stock-entries',
                'action' => 'delete',
                'model' => 'StockEntry',
                'model_id' => $oldData['id'],
                'description' => 'Stock Entry "' . $oldData['entry_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock entry deleted successfully.'
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

        $model = StockEntry::find($id);
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
