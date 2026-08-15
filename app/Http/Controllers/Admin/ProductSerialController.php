<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductSerialRequest;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Warehouse;
use App\Services\ProductSerialService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductSerialController extends Controller
{
    use ActivityLogger;

    protected $productSerialService;

    public function __construct(ProductSerialService $productSerialService)
    {
        $this->productSerialService = $productSerialService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ProductSerial::query()->with(['product', 'warehouse']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by serial status
            if ($request->serial_status) {
                $query->where('serial_status', $request->serial_status);
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
                    $q->where('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.product-serials.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('serial_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->serial_number . '</b><br><small>' . ($row->product->name ?? '-') . '</small>';
                })
                ->addColumn('warehouse_name', function ($row) {
                    return $row->warehouse->name ?? '-';
                })
                ->addColumn('serial_status_badge', function ($row) {
                    $colors = [
                        'in_stock' => 'success',
                        'sold' => 'info',
                        'defective' => 'danger',
                        'returned' => 'warning',
                    ];
                    $color = $colors[$row->serial_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $row->serial_status)) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.product-serials.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'serial_number', 'serial_status_badge', 'action'])
                ->make(true);
        }

        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.product-serials.index', compact('products', 'warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.product-serials.create', compact('products', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductSerialRequest $request)
    {
        DB::beginTransaction();

        try {
            $productSerial = $this->productSerialService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-serials',
                'action' => 'create',
                'model' => 'ProductSerial',
                'model_id' => $productSerial->id,
                'description' => 'Product Serial "' . $productSerial->serial_number . '" created',
                'new_data' => $productSerial->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product serial created successfully.'
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
    public function edit(ProductSerial $productSerial)
    {
        $products = Product::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('admin.product-serials.edit', compact('productSerial', 'products', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductSerialRequest $request, ProductSerial $productSerial)
    {
        DB::beginTransaction();

        try {
            $oldData = $productSerial->toArray();
            $updatedProductSerial = $this->productSerialService->update($productSerial, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-serials',
                'action' => 'update',
                'model' => 'ProductSerial',
                'model_id' => $productSerial->id,
                'description' => 'Product Serial "' . $productSerial->serial_number . '" updated',
                'new_data' => $updatedProductSerial->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.product-serials.index'),
                'message' => 'Product serial updated successfully.'
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
    public function destroy(ProductSerial $productSerial)
    {
        DB::beginTransaction();

        try {
            $oldData = $productSerial->toArray();

            $this->productSerialService->delete($productSerial);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'product-serials',
                'action' => 'delete',
                'model' => 'ProductSerial',
                'model_id' => $oldData['id'],
                'description' => 'Product Serial "' . $oldData['serial_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product serial deleted successfully.'
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

        $model = ProductSerial::find($id);
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
