<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductVariantController extends Controller
{
    use ActivityLogger;

    protected $productVariantService;

    public function __construct(ProductVariantService $productVariantService)
    {
        $this->productVariantService = $productVariantService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProductVariant::query()->with(['product', 'attributeValues']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by product
            if ($request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.product-variants.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('variant', function ($row) {
                    $combo = $row->attributeValues->pluck('value')->implode(' / ');
                    return '<b class="tl-name-txt">' . ($row->product->name ?? '-') . ' — ' . $combo . '</b><br><small>' . $row->sku . '</small>';
                })
                ->addColumn('price_formatted', function ($row) {
                    return $row->price !== null ? number_format($row->price, 2) : (($row->product->selling_price ?? null) !== null ? number_format($row->product->selling_price, 2) . ' (default)' : '-');
                })
                ->addColumn('action', function ($row) {
                    return view('admin.product-variants.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'variant', 'action'])
                ->make(true);
        }

        $products = Product::active()->get();

        return view('admin.product-variants.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::active()->get();
        $attributeValues = AttributeValue::active()->with('productAttribute')->get()->groupBy(fn($av) => $av->productAttribute->name ?? 'Other');

        return view('admin.product-variants.create', compact('products', 'attributeValues'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductVariantRequest $request)
    {
        DB::beginTransaction();

        try {
            $variant = $this->productVariantService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-variants',
                'action' => 'create',
                'model' => 'ProductVariant',
                'model_id' => $variant->id,
                'description' => 'Product Variant "' . $variant->sku . '" created',
                'new_data' => $variant->load('attributeValues')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product variant created successfully.'
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
    public function edit(ProductVariant $productVariant)
    {
        $products = Product::active()->get();
        $attributeValues = AttributeValue::active()->with('productAttribute')->get()->groupBy(fn($av) => $av->productAttribute->name ?? 'Other');
        $selectedAttributeValueIds = $productVariant->attributeValues()->pluck('attribute_values.id')->all();

        return view('admin.product-variants.edit', compact('productVariant', 'products', 'attributeValues', 'selectedAttributeValueIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductVariantRequest $request, ProductVariant $productVariant)
    {
        DB::beginTransaction();

        try {
            $oldData = $productVariant->load('attributeValues')->toArray();
            $updatedVariant = $this->productVariantService->update($productVariant, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-variants',
                'action' => 'update',
                'model' => 'ProductVariant',
                'model_id' => $productVariant->id,
                'description' => 'Product Variant "' . $productVariant->sku . '" updated',
                'new_data' => $updatedVariant->load('attributeValues')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.product-variants.index'),
                'message' => 'Product variant updated successfully.'
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
    public function destroy(ProductVariant $productVariant)
    {
        DB::beginTransaction();

        try {
            $oldData = $productVariant->load('attributeValues')->toArray();

            $this->productVariantService->delete($productVariant);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-variants',
                'action' => 'delete',
                'model' => 'ProductVariant',
                'model_id' => $oldData['id'],
                'description' => 'Product Variant "' . $oldData['sku'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product variant deleted successfully.'
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

        $model = ProductVariant::find($id);
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
