<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuotationRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Services\QuotationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class QuotationController extends Controller
{
    use ActivityLogger;

    protected $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Quotation::query()->with(['lead', 'contact', 'company', 'opportunity', 'createdBy']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
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
                      ->orWhereHas('company', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.quotations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('quotation_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->quotation_number . '</b><br><small>' . ($row->company->name ?? $row->lead->name ?? '-') . '</small>';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return number_format($row->amount, 2);
                })
                ->addColumn('quotation_status_badge', function ($row) {
                    return ucfirst($row->quotation_status);
                })
                ->addColumn('issue_date_formatted', function ($row) {
                    return $row->issue_date ? $row->issue_date->format('d M, Y') : '-';
                })
                ->addColumn('valid_until_formatted', function ($row) {
                    return $row->valid_until ? $row->valid_until->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.quotations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'quotation_number', 'action'])
                ->make(true);
        }

        return view('admin.quotations.index', ['statuses' => Quotation::STATUSES]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $opportunities = Opportunity::active()->get();

        return view('admin.quotations.create', compact('leads', 'contacts', 'companies', 'opportunities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuotationRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['created_by'] = auth()->guard('admin')->id();

            $quotation = $this->quotationService->create($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'quotations',
                'action' => 'create',
                'model' => 'Quotation',
                'model_id' => $quotation->id,
                'description' => 'Quotation "' . $quotation->quotation_number . '" created',
                'new_data' => $quotation->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Quotation created successfully.'
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
    public function edit(Quotation $quotation)
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $opportunities = Opportunity::active()->get();

        return view('admin.quotations.edit', compact('quotation', 'leads', 'contacts', 'companies', 'opportunities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuotationRequest $request, Quotation $quotation)
    {
        DB::beginTransaction();

        try {
            $oldData = $quotation->toArray();
            $updatedQuotation = $this->quotationService->update($quotation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'quotations',
                'action' => 'update',
                'model' => 'Quotation',
                'model_id' => $quotation->id,
                'description' => 'Quotation "' . $quotation->quotation_number . '" updated',
                'new_data' => $updatedQuotation->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.quotations.index'),
                'message' => 'Quotation updated successfully.'
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
    public function destroy(Quotation $quotation)
    {
        DB::beginTransaction();

        try {
            $oldData = $quotation->toArray();

            $this->quotationService->delete($quotation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'quotations',
                'action' => 'delete',
                'model' => 'Quotation',
                'model_id' => $oldData['id'],
                'description' => 'Quotation "' . $oldData['quotation_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Quotation deleted successfully.'
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

        $model = Quotation::find($id);
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
