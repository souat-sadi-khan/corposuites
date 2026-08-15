<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplierQuotationRequest;
use App\Models\Product;
use App\Models\Rfq;
use App\Models\SupplierQuotation;
use App\Models\Vendor;
use App\Services\SupplierQuotationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SupplierQuotationController extends Controller
{
    use ActivityLogger;

    protected $supplierQuotationService;

    public function __construct(SupplierQuotationService $supplierQuotationService)
    {
        $this->supplierQuotationService = $supplierQuotationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SupplierQuotation::query()->with(['rfq', 'vendor'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by quotation status
            if ($request->quotation_status) {
                $query->where('quotation_status', $request->quotation_status);
            }

            // Filter by vendor
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Filter by RFQ
            if ($request->rfq_id) {
                $query->where('rfq_id', $request->rfq_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('quotation_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.supplier-quotations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('quotation_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->quotation_number . '</b><br><small>' . ($row->vendor->name ?? '-') . '</small>';
                })
                ->addColumn('rfq_number_label', function ($row) {
                    return $row->rfq->rfq_number ?? '-';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('quotation_date_formatted', function ($row) {
                    return $row->quotation_date ? $row->quotation_date->format('d M, Y') : '-';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('quotation_status_badge', function ($row) {
                    $colors = [
                        'received' => 'info',
                        'selected' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'dark',
                    ];
                    $color = $colors[$row->quotation_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->quotation_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.supplier-quotations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'quotation_number', 'quotation_status_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->get();
        $rfqs = Rfq::active()->get();

        return view('admin.supplier-quotations.index', compact('vendors', 'rfqs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $rfqs = Rfq::active()->get();
        $vendors = Vendor::active()->get();
        $products = Product::active()->get();

        return view('admin.supplier-quotations.create', compact('rfqs', 'vendors', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierQuotationRequest $request)
    {
        DB::beginTransaction();

        try {
            $supplierQuotation = $this->supplierQuotationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'supplier-quotations',
                'action' => 'create',
                'model' => 'SupplierQuotation',
                'model_id' => $supplierQuotation->id,
                'description' => 'Supplier Quotation "' . $supplierQuotation->quotation_number . '" created',
                'new_data' => $supplierQuotation->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Supplier quotation created successfully.'
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
    public function edit(SupplierQuotation $supplierQuotation)
    {
        $rfqs = Rfq::active()->get();
        $vendors = Vendor::active()->get();
        $products = Product::active()->get();
        $supplierQuotation->load('items');

        return view('admin.supplier-quotations.edit', compact('supplierQuotation', 'rfqs', 'vendors', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierQuotationRequest $request, SupplierQuotation $supplierQuotation)
    {
        DB::beginTransaction();

        try {
            $oldData = $supplierQuotation->load('items')->toArray();
            $updatedSupplierQuotation = $this->supplierQuotationService->update($supplierQuotation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'supplier-quotations',
                'action' => 'update',
                'model' => 'SupplierQuotation',
                'model_id' => $supplierQuotation->id,
                'description' => 'Supplier Quotation "' . $supplierQuotation->quotation_number . '" updated',
                'new_data' => $updatedSupplierQuotation->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.supplier-quotations.index'),
                'message' => 'Supplier quotation updated successfully.'
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
    public function destroy(SupplierQuotation $supplierQuotation)
    {
        DB::beginTransaction();

        try {
            $oldData = $supplierQuotation->load('items')->toArray();

            $this->supplierQuotationService->delete($supplierQuotation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'supplier-quotations',
                'action' => 'delete',
                'model' => 'SupplierQuotation',
                'model_id' => $oldData['id'],
                'description' => 'Supplier Quotation "' . $oldData['quotation_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Supplier quotation deleted successfully.'
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

        $model = SupplierQuotation::find($id);
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
