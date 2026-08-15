<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryRequest;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\DeliveryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DeliveryController extends Controller
{
    use ActivityLogger;

    protected $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Delivery::query()->with(['salesOrder.customer'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by sales order
            if ($request->sales_order_id) {
                $query->where('sales_order_id', $request->sales_order_id);
            }

            // Filter by delivery status
            if ($request->delivery_status) {
                $query->where('delivery_status', $request->delivery_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('delivery_number', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhereHas('salesOrder', function ($oq) use ($search) {
                            $oq->where('order_number', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.deliveries.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('delivery_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->delivery_number . '</b><br><small>' . ($row->salesOrder->order_number ?? '-') . ' &middot; ' . ($row->salesOrder->customer->name ?? '-') . '</small>';
                })
                ->addColumn('delivery_status_badge', function ($row) {
                    $colors = [
                        'pending' => 'secondary',
                        'in_transit' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->delivery_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $row->delivery_status)) . '</span>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('delivery_date_formatted', function ($row) {
                    return $row->delivery_date ? $row->delivery_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.deliveries.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'delivery_number', 'delivery_status_badge', 'action'])
                ->make(true);
        }

        $salesOrders = SalesOrder::active()->get();

        return view('admin.deliveries.index', compact('salesOrders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $salesOrders = SalesOrder::active()->get();
        $products = Product::active()->get();

        return view('admin.deliveries.create', compact('salesOrders', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DeliveryRequest $request)
    {
        DB::beginTransaction();

        try {
            $delivery = $this->deliveryService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'deliveries',
                'action' => 'create',
                'model' => 'Delivery',
                'model_id' => $delivery->id,
                'description' => 'Delivery "' . $delivery->delivery_number . '" created',
                'new_data' => $delivery->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Delivery created successfully.'
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
    public function edit(Delivery $delivery)
    {
        $salesOrders = SalesOrder::active()->get();
        $products = Product::active()->get();
        $delivery->load('items');

        return view('admin.deliveries.edit', compact('delivery', 'salesOrders', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DeliveryRequest $request, Delivery $delivery)
    {
        DB::beginTransaction();

        try {
            $oldData = $delivery->load('items')->toArray();
            $updatedDelivery = $this->deliveryService->update($delivery, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'deliveries',
                'action' => 'update',
                'model' => 'Delivery',
                'model_id' => $delivery->id,
                'description' => 'Delivery "' . $delivery->delivery_number . '" updated',
                'new_data' => $updatedDelivery->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.deliveries.index'),
                'message' => 'Delivery updated successfully.'
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
    public function destroy(Delivery $delivery)
    {
        DB::beginTransaction();

        try {
            $oldData = $delivery->load('items')->toArray();

            $this->deliveryService->delete($delivery);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'deliveries',
                'action' => 'delete',
                'model' => 'Delivery',
                'model_id' => $oldData['id'],
                'description' => 'Delivery "' . $oldData['delivery_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Delivery deleted successfully.'
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

        $model = Delivery::find($id);
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
