<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    use ActivityLogger;

    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::query()->with(['category', 'brand', 'unit']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by category
            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by brand
            if ($request->brand_id) {
                $query->where('brand_id', $request->brand_id);
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.products.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . $row->sku . '</small>';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category->name ?? '-';
                })
                ->addColumn('brand_name', function ($row) {
                    return $row->brand->name ?? '-';
                })
                ->addColumn('unit_code', function ($row) {
                    return $row->unit->short_code ?? '-';
                })
                ->addColumn('selling_price_formatted', function ($row) {
                    return $row->selling_price !== null ? number_format($row->selling_price, 2) : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.products.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        $categories = Category::active()->get();
        $brands = Brand::active()->get();

        return view('admin.products.index', compact('categories', 'brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        $units = Unit::active()->get();

        return view('admin.products.create', compact('categories', 'brands', 'units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        DB::beginTransaction();

        try {
            $product = $this->productService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'products',
                'action' => 'create',
                'model' => 'Product',
                'model_id' => $product->id,
                'description' => 'Product "' . $product->name . '" created',
                'new_data' => $product->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product created successfully.'
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
    public function edit(Product $product)
    {
        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        $units = Unit::active()->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        DB::beginTransaction();

        try {
            $oldData = $product->toArray();
            $updatedProduct = $this->productService->update($product, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'products',
                'action' => 'update',
                'model' => 'Product',
                'model_id' => $product->id,
                'description' => 'Product "' . $product->name . '" updated',
                'new_data' => $updatedProduct->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.products.index'),
                'message' => 'Product updated successfully.'
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
    public function destroy(Product $product)
    {
        DB::beginTransaction();

        try {
            $oldData = $product->toArray();

            $this->productService->delete($product);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'products',
                'action' => 'delete',
                'model' => 'Product',
                'model_id' => $oldData['id'],
                'description' => 'Product "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product deleted successfully.'
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

        $model = Product::find($id);
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
