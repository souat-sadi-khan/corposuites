<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductImageController extends Controller
{
    use ActivityLogger;

    protected $productImageService;

    public function __construct(ProductImageService $productImageService)
    {
        $this->productImageService = $productImageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProductImage::query()->with('product');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by product
            if ($request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            $query->orderBy('sort_order')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.product-images.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('preview', function ($row) {
                    return '<img src="' . asset('storage/' . $row->image_path) . '" alt="preview" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">';
                })
                ->addColumn('name', function ($row) {
                    $primary = $row->is_primary ? '<span class="badge bg-success ms-1">Primary</span>' : '';
                    return '<b class="tl-name-txt">' . ($row->product->name ?? '-') . '</b>' . $primary . '<br><small>Order: ' . $row->sort_order . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.product-images.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'preview', 'name', 'action'])
                ->make(true);
        }

        $products = Product::active()->get();

        return view('admin.product-images.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::active()->get();

        return view('admin.product-images.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductImageRequest $request)
    {
        DB::beginTransaction();

        try {
            $productImage = $this->productImageService->create($request->validated(), $request->file('image'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-images',
                'action' => 'create',
                'model' => 'ProductImage',
                'model_id' => $productImage->id,
                'description' => 'Product Image #' . $productImage->id . ' created',
                'new_data' => $productImage->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product image uploaded successfully.'
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
    public function edit(ProductImage $productImage)
    {
        $products = Product::active()->get();

        return view('admin.product-images.edit', compact('productImage', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductImageRequest $request, ProductImage $productImage)
    {
        DB::beginTransaction();

        try {
            $oldData = $productImage->toArray();
            $updatedProductImage = $this->productImageService->update($productImage, $request->validated(), $request->file('image'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-images',
                'action' => 'update',
                'model' => 'ProductImage',
                'model_id' => $productImage->id,
                'description' => 'Product Image #' . $productImage->id . ' updated',
                'new_data' => $updatedProductImage->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.product-images.index'),
                'message' => 'Product image updated successfully.'
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
    public function destroy(ProductImage $productImage)
    {
        DB::beginTransaction();

        try {
            $oldData = $productImage->toArray();

            $this->productImageService->delete($productImage);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-images',
                'action' => 'delete',
                'model' => 'ProductImage',
                'model_id' => $oldData['id'],
                'description' => 'Product Image #' . $oldData['id'] . ' deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product image deleted successfully.'
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

        $model = ProductImage::find($id);
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
