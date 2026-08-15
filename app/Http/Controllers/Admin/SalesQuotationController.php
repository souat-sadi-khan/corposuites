<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesQuotationRequest;
use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\SalesQuotation;
use App\Services\SalesQuotationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesQuotationController extends Controller
{
    use ActivityLogger;

    protected $salesQuotationService;

    public function __construct(SalesQuotationService $salesQuotationService)
    {
        $this->salesQuotationService = $salesQuotationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesQuotation::query()->with(['customer', 'paymentTerm'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by customer
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by quotation status
            if ($request->quotation_status) {
                $query->where('quotation_status', $request->quotation_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('quotation_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.sales-quotations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('quotation_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->quotation_number . '</b><br><small>' . ($row->customer->name ?? '-') . '</small>';
                })
                ->addColumn('quotation_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'warning',
                    ];
                    $color = $colors[$row->quotation_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->quotation_status) . '</span>';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('issue_date_formatted', function ($row) {
                    return $row->issue_date ? $row->issue_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.sales-quotations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'quotation_number', 'quotation_status_badge', 'action'])
                ->make(true);
        }

        $customers = Customer::active()->get();

        return view('admin.sales-quotations.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::active()->get();
        $paymentTerms = PaymentTerm::active()->get();
        $products = Product::active()->get();

        return view('admin.sales-quotations.create', compact('customers', 'paymentTerms', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesQuotationRequest $request)
    {
        DB::beginTransaction();

        try {
            $salesQuotation = $this->salesQuotationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-quotations',
                'action' => 'create',
                'model' => 'SalesQuotation',
                'model_id' => $salesQuotation->id,
                'description' => 'Sales Quotation "' . $salesQuotation->quotation_number . '" created',
                'new_data' => $salesQuotation->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales quotation created successfully.'
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
    public function edit(SalesQuotation $salesQuotation)
    {
        $customers = Customer::active()->get();
        $paymentTerms = PaymentTerm::active()->get();
        $products = Product::active()->get();
        $salesQuotation->load('items');

        return view('admin.sales-quotations.edit', compact('salesQuotation', 'customers', 'paymentTerms', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesQuotationRequest $request, SalesQuotation $salesQuotation)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesQuotation->load('items')->toArray();
            $updatedSalesQuotation = $this->salesQuotationService->update($salesQuotation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-quotations',
                'action' => 'update',
                'model' => 'SalesQuotation',
                'model_id' => $salesQuotation->id,
                'description' => 'Sales Quotation "' . $salesQuotation->quotation_number . '" updated',
                'new_data' => $updatedSalesQuotation->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.sales-quotations.index'),
                'message' => 'Sales quotation updated successfully.'
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
    public function destroy(SalesQuotation $salesQuotation)
    {
        DB::beginTransaction();

        try {
            $oldData = $salesQuotation->load('items')->toArray();

            $this->salesQuotationService->delete($salesQuotation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'sales-quotations',
                'action' => 'delete',
                'model' => 'SalesQuotation',
                'model_id' => $oldData['id'],
                'description' => 'Sales Quotation "' . $oldData['quotation_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales quotation deleted successfully.'
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

        $model = SalesQuotation::find($id);
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
