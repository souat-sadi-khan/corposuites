<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderLevelRequest;
use App\Models\Product;
use App\Models\ReorderLevel;
use App\Models\Warehouse;
use App\Services\ReorderLevelService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReorderLevelController extends Controller
{
    use ActivityLogger;

    protected $reorderLevelService;

    public function __construct(ReorderLevelService $reorderLevelService)
    {
        $this->reorderLevelService = $reorderLevelService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ReorderLevel::query()->with(['product', 'warehouse']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by product
            if ($request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by warehouse
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->whereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.reorder-levels.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('product_name', function ($row) {
                    return '<b class="tl-name-txt">' . ($row->product->name ?? '-') . '</b><br><small>' . ($row->product->sku ?? '-') . '</small>';
                })
                ->addColumn('warehouse_name', function ($row) {
                    return $row->warehouse->name ?? 'All Warehouses';
                })
                ->addColumn('reorder_level_formatted', function ($row) {
                    return number_format($row->reorder_level, 2);
                })
                ->addColumn('reorder_quantity_formatted', function ($row) {
                    return $row->reorder_quantity !== null ? number_format($row->reorder_quantity, 2) : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.reorder-levels.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'product_name', 'action'])
                ->make(true);
        }

        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.reorder-levels.index', compact('products', 'warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.reorder-levels.create', compact('products', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReorderLevelRequest $request)
    {
        DB::beginTransaction();

        try {
            $reorderLevel = $this->reorderLevelService->create($request->validated());
            $reorderLevel->load('product', 'warehouse');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'reorder-levels',
                'action' => 'create',
                'model' => 'ReorderLevel',
                'model_id' => $reorderLevel->id,
                'description' => 'Reorder Level for "' . ($reorderLevel->product->name ?? '-') . '" created',
                'new_data' => $reorderLevel->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Reorder level created successfully.'
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
    public function edit(ReorderLevel $reorderLevel)
    {
        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.reorder-levels.edit', compact('reorderLevel', 'products', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReorderLevelRequest $request, ReorderLevel $reorderLevel)
    {
        DB::beginTransaction();

        try {
            $oldData = $reorderLevel->toArray();
            $updatedReorderLevel = $this->reorderLevelService->update($reorderLevel, $request->validated());
            $updatedReorderLevel->load('product', 'warehouse');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'reorder-levels',
                'action' => 'update',
                'model' => 'ReorderLevel',
                'model_id' => $reorderLevel->id,
                'description' => 'Reorder Level for "' . ($updatedReorderLevel->product->name ?? '-') . '" updated',
                'new_data' => $updatedReorderLevel->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.reorder-levels.index'),
                'message' => 'Reorder level updated successfully.'
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
    public function destroy(ReorderLevel $reorderLevel)
    {
        DB::beginTransaction();

        try {
            $oldData = $reorderLevel->load('product')->toArray();

            $this->reorderLevelService->delete($reorderLevel);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'reorder-levels',
                'action' => 'delete',
                'model' => 'ReorderLevel',
                'model_id' => $oldData['id'],
                'description' => 'Reorder Level for "' . ($oldData['product']['name'] ?? '-') . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Reorder level deleted successfully.'
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

        $model = ReorderLevel::find($id);
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
