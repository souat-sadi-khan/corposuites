<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesInvoiceRequest;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Services\SalesInvoiceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
{
    use ActivityLogger;

    protected $salesInvoiceService;

    public function __construct(SalesInvoiceService $salesInvoiceService)
    {
        $this->salesInvoiceService = $salesInvoiceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesInvoice::query()->with(['customer', 'paymentTerm', 'salesOrder'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by customer
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by invoice status
            if ($request->invoice_status) {
                $query->where('invoice_status', $request->invoice_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.sales-invoices.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('invoice_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->invoice_number . '</b><br><small>' . ($row->customer->name ?? '-') . '</small>';
                })
                ->addColumn('invoice_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'sent' => 'info',
                        'partially_paid' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'dark',
                    ];
                    $color = $colors[$row->invoice_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $row->invoice_status)) . '</span>';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('balance_due_formatted', function ($row) {
                    return number_format($row->balance_due, 2);
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('invoice_date_formatted', function ($row) {
                    return $row->invoice_date ? $row->invoice_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.sales-invoices.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'invoice_number', 'invoice_status_badge', 'action'])
                ->make(true);
        }

        $customers = Customer::active()->get();

        return view('admin.sales-invoices.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::active()->get();
        $admins = Admin::all();
        $paymentTerms = PaymentTerm::active()->get();
        $salesOrders = SalesOrder::active()->get();
        $products = Product::active()->get();

        return view('admin.sales-invoices.create', compact('customers', 'admins', 'paymentTerms', 'salesOrders', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesInvoiceRequest $request)
    {
        DB::beginTransaction();

        try {
            $salesInvoice = $this->salesInvoiceService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-invoices',
                'action' => 'create',
                'model' => 'SalesInvoice',
                'model_id' => $salesInvoice->id,
                'description' => 'Sales Invoice "' . $salesInvoice->invoice_number . '" created',
                'new_data' => $salesInvoice->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales invoice created successfully.'
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
    public function edit(SalesInvoice $salesInvoice)
    {
        $customers = Customer::active()->get();
        $admins = Admin::all();
        $paymentTerms = PaymentTerm::active()->get();
        $salesOrders = SalesOrder::active()->get();
        $products = Product::active()->get();
        $salesInvoice->load('items');

        return view('admin.sales-invoices.edit', compact('salesInvoice', 'customers', 'admins', 'paymentTerms', 'salesOrders', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesInvoiceRequest $request, SalesInvoice $salesInvoice)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesInvoice->load('items')->toArray();
            $updatedSalesInvoice = $this->salesInvoiceService->update($salesInvoice, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-invoices',
                'action' => 'update',
                'model' => 'SalesInvoice',
                'model_id' => $salesInvoice->id,
                'description' => 'Sales Invoice "' . $salesInvoice->invoice_number . '" updated',
                'new_data' => $updatedSalesInvoice->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.sales-invoices.index'),
                'message' => 'Sales invoice updated successfully.'
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
    public function destroy(SalesInvoice $salesInvoice)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesInvoice->load('items')->toArray();

            $this->salesInvoiceService->delete($salesInvoice);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-invoices',
                'action' => 'delete',
                'model' => 'SalesInvoice',
                'model_id' => $oldData['id'],
                'description' => 'Sales Invoice "' . $oldData['invoice_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales invoice deleted successfully.'
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

        $model = SalesInvoice::find($id);
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
