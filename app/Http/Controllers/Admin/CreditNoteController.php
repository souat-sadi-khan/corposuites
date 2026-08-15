<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreditNoteRequest;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Services\CreditNoteService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CreditNoteController extends Controller
{
    use ActivityLogger;

    protected $creditNoteService;

    public function __construct(CreditNoteService $creditNoteService)
    {
        $this->creditNoteService = $creditNoteService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CreditNote::query()->with(['customer', 'salesInvoice'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by customer
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            // Filter by credit status
            if ($request->credit_status) {
                $query->where('credit_status', $request->credit_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('credit_note_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.credit-notes.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('credit_note_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->credit_note_number . '</b><br><small>' . ($row->customer->name ?? '-') . '</small>';
                })
                ->addColumn('credit_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'issued' => 'info',
                        'applied' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->credit_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->credit_status) . '</span>';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('credit_date_formatted', function ($row) {
                    return $row->credit_date ? $row->credit_date->format('d M, Y') : '-';
                })
                ->addColumn('invoice_number_label', function ($row) {
                    return $row->salesInvoice->invoice_number ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.credit-notes.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'credit_note_number', 'credit_status_badge', 'action'])
                ->make(true);
        }

        $customers = Customer::active()->get();

        return view('admin.credit-notes.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::active()->get();
        $salesInvoices = SalesInvoice::active()->get();
        $products = Product::active()->get();

        return view('admin.credit-notes.create', compact('customers', 'salesInvoices', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreditNoteRequest $request)
    {
        DB::beginTransaction();

        try {
            $creditNote = $this->creditNoteService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'credit-notes',
                'action' => 'create',
                'model' => 'CreditNote',
                'model_id' => $creditNote->id,
                'description' => 'Credit Note "' . $creditNote->credit_note_number . '" created',
                'new_data' => $creditNote->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Credit note created successfully.'
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
    public function edit(CreditNote $creditNote)
    {
        $customers = Customer::active()->get();
        $salesInvoices = SalesInvoice::active()->get();
        $products = Product::active()->get();
        $creditNote->load('items');

        return view('admin.credit-notes.edit', compact('creditNote', 'customers', 'salesInvoices', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreditNoteRequest $request, CreditNote $creditNote)
    {
        DB::beginTransaction();

        try {
            $oldData = $creditNote->load('items')->toArray();
            $updatedCreditNote = $this->creditNoteService->update($creditNote, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'credit-notes',
                'action' => 'update',
                'model' => 'CreditNote',
                'model_id' => $creditNote->id,
                'description' => 'Credit Note "' . $creditNote->credit_note_number . '" updated',
                'new_data' => $updatedCreditNote->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.credit-notes.index'),
                'message' => 'Credit note updated successfully.'
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
    public function destroy(CreditNote $creditNote)
    {
        DB::beginTransaction();

        try {
            $oldData = $creditNote->load('items')->toArray();

            $this->creditNoteService->delete($creditNote);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'credit-notes',
                'action' => 'delete',
                'model' => 'CreditNote',
                'model_id' => $oldData['id'],
                'description' => 'Credit Note "' . $oldData['credit_note_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Credit note deleted successfully.'
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

        $model = CreditNote::find($id);
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
