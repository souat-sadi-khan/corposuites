<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DebitNoteRequest;
use App\Models\DebitNote;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Vendor;
use App\Services\DebitNoteService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DebitNoteController extends Controller
{
    use ActivityLogger;

    protected $debitNoteService;

    public function __construct(DebitNoteService $debitNoteService)
    {
        $this->debitNoteService = $debitNoteService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DebitNote::query()->with(['vendor', 'purchaseInvoice'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by debit status
            if ($request->debit_status) {
                $query->where('debit_status', $request->debit_status);
            }

            // Filter by vendor
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('debit_note_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.debit-notes.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('debit_note_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->debit_note_number . '</b><br><small>' . ($row->vendor->name ?? '-') . '</small>';
                })
                ->addColumn('invoice_number_label', function ($row) {
                    return $row->purchaseInvoice->invoice_number ?? '-';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('debit_date_formatted', function ($row) {
                    return $row->debit_date ? $row->debit_date->format('d M, Y') : '-';
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('debit_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'issued' => 'info',
                        'applied' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->debit_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->debit_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.debit-notes.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'debit_note_number', 'debit_status_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->get();

        return view('admin.debit-notes.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = Vendor::active()->get();
        $purchaseInvoices = PurchaseInvoice::active()->get();
        $products = Product::active()->get();

        return view('admin.debit-notes.create', compact('vendors', 'purchaseInvoices', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DebitNoteRequest $request)
    {
        DB::beginTransaction();

        try {
            $debitNote = $this->debitNoteService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'debit-notes',
                'action' => 'create',
                'model' => 'DebitNote',
                'model_id' => $debitNote->id,
                'description' => 'Debit Note "' . $debitNote->debit_note_number . '" created',
                'new_data' => $debitNote->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Debit note created successfully.'
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
    public function edit(DebitNote $debitNote)
    {
        $vendors = Vendor::active()->get();
        $purchaseInvoices = PurchaseInvoice::active()->get();
        $products = Product::active()->get();
        $debitNote->load('items');

        return view('admin.debit-notes.edit', compact('debitNote', 'vendors', 'purchaseInvoices', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DebitNoteRequest $request, DebitNote $debitNote)
    {
        DB::beginTransaction();

        try {
            $oldData = $debitNote->load('items')->toArray();
            $updatedDebitNote = $this->debitNoteService->update($debitNote, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'debit-notes',
                'action' => 'update',
                'model' => 'DebitNote',
                'model_id' => $debitNote->id,
                'description' => 'Debit Note "' . $debitNote->debit_note_number . '" updated',
                'new_data' => $updatedDebitNote->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.debit-notes.index'),
                'message' => 'Debit note updated successfully.'
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
    public function destroy(DebitNote $debitNote)
    {
        DB::beginTransaction();

        try {
            $oldData = $debitNote->load('items')->toArray();

            $this->debitNoteService->delete($debitNote);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'debit-notes',
                'action' => 'delete',
                'model' => 'DebitNote',
                'model_id' => $oldData['id'],
                'description' => 'Debit Note "' . $oldData['debit_note_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Debit note deleted successfully.'
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

        $model = DebitNote::find($id);
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
