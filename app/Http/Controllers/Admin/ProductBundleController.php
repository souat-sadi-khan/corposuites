<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductBundleRequest;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Services\ProductBundleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductBundleController extends Controller
{
    use ActivityLogger;

    protected $productBundleService;

    public function __construct(ProductBundleService $productBundleService)
    {
        $this->productBundleService = $productBundleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProductBundle::query()->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.product-bundles.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . $row->sku . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' product' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('price_formatted', function ($row) {
                    return $row->price !== null ? number_format($row->price, 2) : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.product-bundles.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.product-bundles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::active()->get();

        return view('admin.product-bundles.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductBundleRequest $request)
    {
        DB::beginTransaction();

        try {
            $productBundle = $this->productBundleService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-bundles',
                'action' => 'create',
                'model' => 'ProductBundle',
                'model_id' => $productBundle->id,
                'description' => 'Product Bundle "' . $productBundle->name . '" created',
                'new_data' => $productBundle->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product bundle created successfully.'
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
    public function edit(ProductBundle $productBundle)
    {
        $products = Product::active()->get();
        $productBundle->load('items');

        return view('admin.product-bundles.edit', compact('productBundle', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductBundleRequest $request, ProductBundle $productBundle)
    {
        DB::beginTransaction();

        try {
            $oldData = $productBundle->load('items')->toArray();
            $updatedProductBundle = $this->productBundleService->update($productBundle, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-bundles',
                'action' => 'update',
                'model' => 'ProductBundle',
                'model_id' => $productBundle->id,
                'description' => 'Product Bundle "' . $productBundle->name . '" updated',
                'new_data' => $updatedProductBundle->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.product-bundles.index'),
                'message' => 'Product bundle updated successfully.'
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
    public function destroy(ProductBundle $productBundle)
    {
        DB::beginTransaction();

        try {
            $oldData = $productBundle->load('items')->toArray();

            $this->productBundleService->delete($productBundle);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-bundles',
                'action' => 'delete',
                'model' => 'ProductBundle',
                'model_id' => $oldData['id'],
                'description' => 'Product Bundle "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product bundle deleted successfully.'
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

        $model = ProductBundle::find($id);
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
