<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentReceiveRequest;
use App\Models\Customer;
use App\Models\FinanceBankAccount;
use App\Models\PaymentReceive;
use App\Models\SalesInvoice;
use App\Services\PaymentReceiveService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PaymentReceiveController extends Controller
{
    use ActivityLogger;

    protected $paymentReceiveService;

    public function __construct(PaymentReceiveService $paymentReceiveService)
    {
        $this->paymentReceiveService = $paymentReceiveService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PaymentReceive::query()->with(['customer', 'financeBankAccount'])->withCount('items');

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->payment_method) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.payment-receives.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('payment_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->payment_number . '</b><br><small>' . ($row->customer->name ?? '-') . '</small>';
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
                    return view('admin.payment-receives.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'payment_number', 'payment_method_badge', 'action'])
                ->make(true);
        }

        $customers = Customer::active()->get();

        return view('admin.payment-receives.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::active()->get();
        $financeBankAccounts = FinanceBankAccount::active()->get();
        $openInvoices = $this->openInvoices();

        return view('admin.payment-receives.create', compact('customers', 'financeBankAccounts', 'openInvoices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentReceiveRequest $request)
    {
        DB::beginTransaction();

        try {
            $paymentReceive = $this->paymentReceiveService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-receives',
                'action' => 'create',
                'model' => 'PaymentReceive',
                'model_id' => $paymentReceive->id,
                'description' => 'Payment Receive "' . $paymentReceive->payment_number . '" created',
                'new_data' => $paymentReceive->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment received successfully.'
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
    public function edit(PaymentReceive $paymentReceive)
    {
        $customers = Customer::active()->get();
        $financeBankAccounts = FinanceBankAccount::active()->get();
        $openInvoices = $this->openInvoices($paymentReceive);
        $paymentReceive->load('items');

        return view('admin.payment-receives.edit', compact('paymentReceive', 'customers', 'financeBankAccounts', 'openInvoices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentReceiveRequest $request, PaymentReceive $paymentReceive)
    {
        DB::beginTransaction();

        try {
            $oldData = $paymentReceive->load('items')->toArray();
            $updated = $this->paymentReceiveService->update($paymentReceive, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-receives',
                'action' => 'update',
                'model' => 'PaymentReceive',
                'model_id' => $paymentReceive->id,
                'description' => 'Payment Receive "' . $paymentReceive->payment_number . '" updated',
                'new_data' => $updated->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.payment-receives.index'),
                'message' => 'Payment receive updated successfully.'
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
    public function destroy(PaymentReceive $paymentReceive)
    {
        DB::beginTransaction();

        try {
            $oldData = $paymentReceive->load('items')->toArray();

            $this->paymentReceiveService->delete($paymentReceive);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-receives',
                'action' => 'delete',
                'model' => 'PaymentReceive',
                'model_id' => $oldData['id'],
                'description' => 'Payment Receive "' . $oldData['payment_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment receive deleted successfully.'
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

        $model = PaymentReceive::find($id);
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
     * Active, non-cancelled Sales Invoices still carrying a balance —
     * the pool the item-row invoice dropdown is built from, filtered
     * client-side by the selected header customer. On edit, this
     * Payment Receive's own already-allocated invoices are included too
     * (via `orWhereIn`), even if fully paid by this same payment, so
     * editing doesn't lose the currently-selected invoice from the list
     * — same "don't lose the current selection" precedent
     * `DebitNoteController`/`BankReconciliationController` already
     * established.
     */
    protected function openInvoices(?PaymentReceive $paymentReceive = null)
    {
        $existingInvoiceIds = $paymentReceive
            ? $paymentReceive->items()->pluck('sales_invoice_id')->all()
            : [];

        return SalesInvoice::with('customer')
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
