<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OpeningStockRequest;
use App\Models\OpeningStock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\OpeningStockService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OpeningStockController extends Controller
{
    use ActivityLogger;

    protected $openingStockService;

    public function __construct(OpeningStockService $openingStockService)
    {
        $this->openingStockService = $openingStockService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = OpeningStock::query()->with(['warehouse', 'warehouseLocation', 'items'])->withCount('items');

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.opening-stocks.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
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
                ->addColumn('opening_date_formatted', function ($row) {
                    return $row->opening_date ? $row->opening_date->format('d M, Y') : '-';
                })
                ->addColumn('total_value_formatted', function ($row) {
                    $total = $row->items->sum(fn ($item) => $item->quantity * $item->unit_cost);
                    return number_format($total, 2);
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
                    return view('admin.opening-stocks.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'entry_number', 'entry_status_badge', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::active()->get();

        return view('admin.opening-stocks.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();

        return view('admin.opening-stocks.create', compact('warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OpeningStockRequest $request)
    {
        DB::beginTransaction();

        try {
            $openingStock = $this->openingStockService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'opening-stocks',
                'action' => 'create',
                'model' => 'OpeningStock',
                'model_id' => $openingStock->id,
                'description' => 'Opening Stock "' . $openingStock->entry_number . '" created',
                'new_data' => $openingStock->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Opening stock created successfully.'
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
    public function edit(OpeningStock $openingStock)
    {
        $warehouses = Warehouse::active()->get();
        $warehouseLocations = WarehouseLocation::active()->get();
        $products = Product::active()->get();
        $openingStock->load('items');

        return view('admin.opening-stocks.edit', compact('openingStock', 'warehouses', 'warehouseLocations', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OpeningStockRequest $request, OpeningStock $openingStock)
    {
        DB::beginTransaction();

        try {
            $oldData = $openingStock->load('items')->toArray();
            $updatedOpeningStock = $this->openingStockService->update($openingStock, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'opening-stocks',
                'action' => 'update',
                'model' => 'OpeningStock',
                'model_id' => $openingStock->id,
                'description' => 'Opening Stock "' . $openingStock->entry_number . '" updated',
                'new_data' => $updatedOpeningStock->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.opening-stocks.index'),
                'message' => 'Opening stock updated successfully.'
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
    public function destroy(OpeningStock $openingStock)
    {
        DB::beginTransaction();

        try {
            $oldData = $openingStock->load('items')->toArray();

            $this->openingStockService->delete($openingStock);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'opening-stocks',
                'action' => 'delete',
                'model' => 'OpeningStock',
                'model_id' => $oldData['id'],
                'description' => 'Opening Stock "' . $oldData['entry_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Opening stock deleted successfully.'
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

        $model = OpeningStock::find($id);
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
