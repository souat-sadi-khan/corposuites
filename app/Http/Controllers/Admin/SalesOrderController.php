<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesOrderRequest;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Services\SalesOrderService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesOrderController extends Controller
{
    use ActivityLogger;

    protected $salesOrderService;

    public function __construct(SalesOrderService $salesOrderService)
    {
        $this->salesOrderService = $salesOrderService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesOrder::query()->with(['customer', 'paymentTerm', 'salesQuotation'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by customer
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by order status
            if ($request->order_status) {
                $query->where('order_status', $request->order_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.sales-orders.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('order_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->order_number . '</b><br><small>' . ($row->customer->name ?? '-') . '</small>';
                })
                ->addColumn('order_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'confirmed' => 'info',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->order_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->order_status) . '</span>';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('order_date_formatted', function ($row) {
                    return $row->order_date ? $row->order_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.sales-orders.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'order_number', 'order_status_badge', 'action'])
                ->make(true);
        }

        $customers = Customer::active()->get();

        return view('admin.sales-orders.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::active()->get();
        $admins = Admin::all();
        $paymentTerms = PaymentTerm::active()->get();
        $salesQuotations = SalesQuotation::active()->get();
        $products = Product::active()->get();

        return view('admin.sales-orders.create', compact('customers', 'admins', 'paymentTerms', 'salesQuotations', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $salesOrder = $this->salesOrderService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-orders',
                'action' => 'create',
                'model' => 'SalesOrder',
                'model_id' => $salesOrder->id,
                'description' => 'Sales Order "' . $salesOrder->order_number . '" created',
                'new_data' => $salesOrder->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales order created successfully.'
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
    public function edit(SalesOrder $salesOrder)
    {
        $customers = Customer::active()->get();
        $admins = Admin::all();
        $paymentTerms = PaymentTerm::active()->get();
        $salesQuotations = SalesQuotation::active()->get();
        $products = Product::active()->get();
        $salesOrder->load('items');

        return view('admin.sales-orders.edit', compact('salesOrder', 'customers', 'admins', 'paymentTerms', 'salesQuotations', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesOrderRequest $request, SalesOrder $salesOrder)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesOrder->load('items')->toArray();
            $updatedSalesOrder = $this->salesOrderService->update($salesOrder, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-orders',
                'action' => 'update',
                'model' => 'SalesOrder',
                'model_id' => $salesOrder->id,
                'description' => 'Sales Order "' . $salesOrder->order_number . '" updated',
                'new_data' => $updatedSalesOrder->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.sales-orders.index'),
                'message' => 'Sales order updated successfully.'
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
    public function destroy(SalesOrder $salesOrder)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesOrder->load('items')->toArray();

            $this->salesOrderService->delete($salesOrder);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-orders',
                'action' => 'delete',
                'model' => 'SalesOrder',
                'model_id' => $oldData['id'],
                'description' => 'Sales Order "' . $oldData['order_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales order deleted successfully.'
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

        $model = SalesOrder::find($id);
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
