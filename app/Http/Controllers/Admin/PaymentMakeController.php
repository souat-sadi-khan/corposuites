<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMakeRequest;
use App\Models\FinanceBankAccount;
use App\Models\PaymentMake;
use App\Models\PurchaseInvoice;
use App\Models\Vendor;
use App\Services\PaymentMakeService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PaymentMakeController extends Controller
{
    use ActivityLogger;

    protected $paymentMakeService;

    public function __construct(PaymentMakeService $paymentMakeService)
    {
        $this->paymentMakeService = $paymentMakeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PaymentMake::query()->with(['vendor', 'financeBankAccount'])->withCount('items');

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            if ($request->payment_method) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.payment-makes.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('payment_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->payment_number . '</b><br><small>' . ($row->vendor->name ?? '-') . '</small>';
                })
                ->addColumn('payment_method_badge', function ($row) {
                    return '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $row->payment_method)) . '</span>';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return number_format($row->amount, 2);
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' invoice' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('payment_date_formatted', function ($row) {
                    return $row->payment_date ? $row->payment_date->format('d M, Y') : '-';
                })
                ->addColumn('bank_account_label', function ($row) {
                    return $row->financeBankAccount->bank_name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.payment-makes.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'payment_number', 'payment_method_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->get();

        return view('admin.payment-makes.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = Vendor::active()->get();
        $financeBankAccounts = FinanceBankAccount::active()->get();
        $openInvoices = $this->openInvoices();

        return view('admin.payment-makes.create', compact('vendors', 'financeBankAccounts', 'openInvoices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentMakeRequest $request)
    {
        DB::beginTransaction();

        try {
            $paymentMake = $this->paymentMakeService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-makes',
                'action' => 'create',
                'model' => 'PaymentMake',
                'model_id' => $paymentMake->id,
                'description' => 'Payment Make "' . $paymentMake->payment_number . '" created',
                'new_data' => $paymentMake->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment recorded successfully.'
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
    public function edit(PaymentMake $paymentMake)
    {
        $vendors = Vendor::active()->get();
        $financeBankAccounts = FinanceBankAccount::active()->get();
        $openInvoices = $this->openInvoices($paymentMake);
        $paymentMake->load('items');

        return view('admin.payment-makes.edit', compact('paymentMake', 'vendors', 'financeBankAccounts', 'openInvoices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentMakeRequest $request, PaymentMake $paymentMake)
    {
        DB::beginTransaction();

        try {
            $oldData = $paymentMake->load('items')->toArray();
            $updated = $this->paymentMakeService->update($paymentMake, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-makes',
                'action' => 'update',
                'model' => 'PaymentMake',
                'model_id' => $paymentMake->id,
                'description' => 'Payment Make "' . $paymentMake->payment_number . '" updated',
                'new_data' => $updated->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.payment-makes.index'),
                'message' => 'Payment make updated successfully.'
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
    public function destroy(PaymentMake $paymentMake)
    {
        DB::beginTransaction();

        try {
            $oldData = $paymentMake->load('items')->toArray();

            $this->paymentMakeService->delete($paymentMake);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-makes',
                'action' => 'delete',
                'model' => 'PaymentMake',
                'model_id' => $oldData['id'],
                'description' => 'Payment Make "' . $oldData['payment_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment make deleted successfully.'
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

        $model = PaymentMake::find($id);
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

    /**
     * Active, non-cancelled Purchase Invoices still carrying a balance —
     * the pool the item-row invoice dropdown is built from, filtered
     * client-side by the selected header vendor. On edit, this Payment
     * Make's own already-allocated invoices are included too (via
     * `orWhereIn`), even if fully paid by this same payment, so editing
     * doesn't lose the currently-selected invoice from the list — same
     * "don't lose the current selection" precedent `PaymentReceiveController`
     * already established.
     */
    protected function openInvoices(?PaymentMake $paymentMake = null)
    {
        $existingInvoiceIds = $paymentMake
            ? $paymentMake->items()->pluck('purchase_invoice_id')->all()
            : [];

        return PurchaseInvoice::with('vendor')
            ->where('status', 1)
            ->where('invoice_status', '!=', 'cancelled')
            ->where(function ($q) use ($existingInvoiceIds) {
                $q->whereColumn('amount_paid', '<', 'grand_total');
                if (!empty($existingInvoiceIds)) {
                    $q->orWhereIn('id', $existingInvoiceIds);
                }
            })
            ->orderBy('invoice_date')
            ->get();
    }
}
