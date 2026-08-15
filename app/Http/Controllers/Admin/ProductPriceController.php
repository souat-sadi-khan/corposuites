<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductPriceRequest;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ProductPriceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductPriceController extends Controller
{
    use ActivityLogger;

    protected $productPriceService;

    public function __construct(ProductPriceService $productPriceService)
    {
        $this->productPriceService = $productPriceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProductPrice::query()->with(['product', 'priceTier']);

            // Filter by product
            if ($request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by tier
            if ($request->price_tier_id) {
                $query->where('price_tier_id', $request->price_tier_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('product_name', function ($row) {
                    return '<b class="tl-name-txt">' . ($row->product->name ?? '-') . '</b><br><small>' . ($row->product->sku ?? '') . '</small>';
                })
                ->addColumn('tier_name', function ($row) {
                    return $row->priceTier->name ?? '-';
                })
                ->addColumn('price_formatted', function ($row) {
                    return number_format($row->price, 2);
                })
                ->addColumn('action', function ($row) {
                    return view('admin.product-prices.action', compact('row'))->render();
                })
                ->rawColumns(['product_name', 'action'])
                ->make(true);
        }

        $products = Product::active()->get();
        $priceTiers = PriceTier::active()->get();

        return view('admin.product-prices.index', compact('products', 'priceTiers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::active()->get();
        $priceTiers = PriceTier::active()->get();

        return view('admin.product-prices.create', compact('products', 'priceTiers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductPriceRequest $request)
    {
        DB::beginTransaction();

        try {
            $productPrice = $this->productPriceService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-prices',
                'action' => 'create',
                'model' => 'ProductPrice',
                'model_id' => $productPrice->id,
                'description' => 'Product Price #' . $productPrice->id . ' created',
                'new_data' => $productPrice->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product price created successfully.'
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
    public function edit(ProductPrice $productPrice)
    {
        $products = Product::active()->get();
        $priceTiers = PriceTier::active()->get();

        return view('admin.product-prices.edit', compact('productPrice', 'products', 'priceTiers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductPriceRequest $request, ProductPrice $productPrice)
    {
        DB::beginTransaction();

        try {
            $oldData = $productPrice->toArray();
            $updatedProductPrice = $this->productPriceService->update($productPrice, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-prices',
                'action' => 'update',
                'model' => 'ProductPrice',
                'model_id' => $productPrice->id,
                'description' => 'Product Price #' . $productPrice->id . ' updated',
                'new_data' => $updatedProductPrice->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.product-prices.index'),
                'message' => 'Product price updated successfully.'
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
    public function destroy(ProductPrice $productPrice)
    {
        DB::beginTransaction();

        try {
            $oldData = $productPrice->toArray();

            $this->productPriceService->delete($productPrice);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-prices',
                'action' => 'delete',
                'model' => 'ProductPrice',
                'model_id' => $oldData['id'],
                'description' => 'Product Price #' . $oldData['id'] . ' deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product price deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
