<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesReturnRequest;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Services\SalesReturnService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesReturnController extends Controller
{
    use ActivityLogger;

    protected $salesReturnService;

    public function __construct(SalesReturnService $salesReturnService)
    {
        $this->salesReturnService = $salesReturnService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesReturn::query()->with(['customer', 'salesOrder', 'delivery'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by customer
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by return status
            if ($request->return_status) {
                $query->where('return_status', $request->return_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.sales-returns.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('return_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->return_number . '</b><br><small>' . ($row->customer->name ?? '-') . '</small>';
                })
                ->addColumn('return_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'received' => 'info',
                        'inspected' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->return_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->return_status) . '</span>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('return_date_formatted', function ($row) {
                    return $row->return_date ? $row->return_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.sales-returns.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'return_number', 'return_status_badge', 'action'])
                ->make(true);
        }

        $customers = Customer::active()->get();

        return view('admin.sales-returns.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::active()->get();
        $salesOrders = SalesOrder::active()->get();
        $deliveries = Delivery::active()->get();
        $products = Product::active()->get();

        return view('admin.sales-returns.create', compact('customers', 'salesOrders', 'deliveries', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesReturnRequest $request)
    {
        DB::beginTransaction();

        try {
            $salesReturn = $this->salesReturnService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-returns',
                'action' => 'create',
                'model' => 'SalesReturn',
                'model_id' => $salesReturn->id,
                'description' => 'Sales Return "' . $salesReturn->return_number . '" created',
                'new_data' => $salesReturn->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales return created successfully.'
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
    public function edit(SalesReturn $salesReturn)
    {
        $customers = Customer::active()->get();
        $salesOrders = SalesOrder::active()->get();
        $deliveries = Delivery::active()->get();
        $products = Product::active()->get();
        $salesReturn->load('items');

        return view('admin.sales-returns.edit', compact('salesReturn', 'customers', 'salesOrders', 'deliveries', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesReturnRequest $request, SalesReturn $salesReturn)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesReturn->load('items')->toArray();
            $updatedSalesReturn = $this->salesReturnService->update($salesReturn, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-returns',
                'action' => 'update',
                'model' => 'SalesReturn',
                'model_id' => $salesReturn->id,
                'description' => 'Sales Return "' . $salesReturn->return_number . '" updated',
                'new_data' => $updatedSalesReturn->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.sales-returns.index'),
                'message' => 'Sales return updated successfully.'
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
    public function destroy(SalesReturn $salesReturn)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesReturn->load('items')->toArray();

            $this->salesReturnService->delete($salesReturn);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-returns',
                'action' => 'delete',
                'model' => 'SalesReturn',
                'model_id' => $oldData['id'],
                'description' => 'Sales Return "' . $oldData['return_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales return deleted successfully.'
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

        $model = SalesReturn::find($id);
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
