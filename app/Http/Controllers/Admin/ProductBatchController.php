<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductBatchRequest;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use App\Services\ProductBatchService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductBatchController extends Controller
{
    use ActivityLogger;

    protected $productBatchService;

    public function __construct(ProductBatchService $productBatchService)
    {
        $this->productBatchService = $productBatchService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProductBatch::query()->with(['product', 'warehouse']);

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
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.product-batches.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('batch_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->batch_number . '</b><br><small>' . ($row->product->name ?? '-') . '</small>';
                })
                ->addColumn('warehouse_name', function ($row) {
                    return $row->warehouse->name ?? '-';
                })
                ->addColumn('quantity_formatted', function ($row) {
                    return number_format($row->quantity, 2);
                })
                ->addColumn('expiry_date_formatted', function ($row) {
                    if (!$row->expiry_date) {
                        return '-';
                    }
                    $isExpired = $row->expiry_date->isPast();
                    $badge = $isExpired ? ' <span class="badge bg-danger">Expired</span>' : '';
                    return $row->expiry_date->format('d M, Y') . $badge;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.product-batches.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'batch_number', 'expiry_date_formatted', 'action'])
                ->make(true);
        }

        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.product-batches.index', compact('products', 'warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.product-batches.create', compact('products', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductBatchRequest $request)
    {
        DB::beginTransaction();

        try {
            $productBatch = $this->productBatchService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-batches',
                'action' => 'create',
                'model' => 'ProductBatch',
                'model_id' => $productBatch->id,
                'description' => 'Product Batch "' . $productBatch->batch_number . '" created',
                'new_data' => $productBatch->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product batch created successfully.'
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
    public function edit(ProductBatch $productBatch)
    {
        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.product-batches.edit', compact('productBatch', 'products', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductBatchRequest $request, ProductBatch $productBatch)
    {
        DB::beginTransaction();

        try {
            $oldData = $productBatch->toArray();
            $updatedProductBatch = $this->productBatchService->update($productBatch, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-batches',
                'action' => 'update',
                'model' => 'ProductBatch',
                'model_id' => $productBatch->id,
                'description' => 'Product Batch "' . $productBatch->batch_number . '" updated',
                'new_data' => $updatedProductBatch->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.product-batches.index'),
                'message' => 'Product batch updated successfully.'
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
    public function destroy(ProductBatch $productBatch)
    {
        DB::beginTransaction();

        try {
            $oldData = $productBatch->toArray();

            $this->productBatchService->delete($productBatch);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-batches',
                'action' => 'delete',
                'model' => 'ProductBatch',
                'model_id' => $oldData['id'],
                'description' => 'Product Batch "' . $oldData['batch_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product batch deleted successfully.'
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

        $model = ProductBatch::find($id);
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
