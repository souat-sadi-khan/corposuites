<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankTransactionRequest;
use App\Models\BankTransaction;
use App\Models\FinanceBankAccount;
use App\Models\JournalEntry;
use App\Services\BankTransactionService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BankTransactionController extends Controller
{
    use ActivityLogger;

    protected $bankTransactionService;

    public function __construct(BankTransactionService $bankTransactionService)
    {
        $this->bankTransactionService = $bankTransactionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = BankTransaction::query()->with('financeBankAccount');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by bank account
            if ($request->finance_bank_account_id) {
                $query->where('finance_bank_account_id', $request->finance_bank_account_id);
            }

            // Filter by transaction type
            if ($request->transaction_type) {
                $query->where('transaction_type', $request->transaction_type);
            }

            // Filter by reconciled state
            if ($request->reconciled !== null && $request->reconciled !== '') {
                $query->where('reconciled', $request->reconciled);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('financeBankAccount', function ($bq) use ($search) {
                            $bq->where('bank_name', 'like', "%{$search}%")
                                ->orWhere('account_number', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.bank-transactions.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('bank_account_label', function ($row) {
                    return '<b class="tl-name-txt">' . ($row->financeBankAccount->bank_name ?? '-') . '</b><br><small>' . ($row->financeBankAccount->account_number ?? '-') . '</small>';
                })
                ->addColumn('transaction_type_badge', function ($row) {
                    $color = $row->transaction_type === 'deposit' ? 'success' : 'danger';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->transaction_type) . '</span>';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return number_format($row->amount, 2);
                })
                ->addColumn('transaction_date_formatted', function ($row) {
                    return $row->transaction_date ? $row->transaction_date->format('d M, Y') : '-';
                })
                ->addColumn('reconciled_badge', function ($row) {
                    return $row->reconciled
                        ? '<span class="badge bg-success">Reconciled</span>'
                        : '<span class="badge bg-secondary">Pending</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.bank-transactions.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'bank_account_label', 'transaction_type_badge', 'reconciled_badge', 'action'])
                ->make(true);
        }

        $bankAccounts = FinanceBankAccount::active()->get();

        return view('admin.bank-transactions.index', compact('bankAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $bankAccounts = FinanceBankAccount::active()->get();
        $journalEntries = JournalEntry::active()->orderByDesc('id')->get();

        return view('admin.bank-transactions.create', compact('bankAccounts', 'journalEntries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BankTransactionRequest $request)
    {
        DB::beginTransaction();

        try {
            $bankTransaction = $this->bankTransactionService->create($request->validated());
            $bankTransaction->load('financeBankAccount');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-transactions',
                'action' => 'create',
                'model' => 'BankTransaction',
                'model_id' => $bankTransaction->id,
                'description' => ucfirst($bankTransaction->transaction_type) . ' of ' . number_format($bankTransaction->amount, 2) . ' recorded for "' . ($bankTransaction->financeBankAccount->bank_name ?? '-') . '"',
                'new_data' => $bankTransaction->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Bank transaction created successfully.'
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
    public function edit(BankTransaction $bankTransaction)
    {
        $bankAccounts = FinanceBankAccount::active()->get();
        $journalEntries = JournalEntry::active()->orderByDesc('id')->get();

        return view('admin.bank-transactions.edit', compact('bankTransaction', 'bankAccounts', 'journalEntries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BankTransactionRequest $request, BankTransaction $bankTransaction)
    {
        DB::beginTransaction();

        try {
            $oldData = $bankTransaction->toArray();
            $updatedBankTransaction = $this->bankTransactionService->update($bankTransaction, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-transactions',
                'action' => 'update',
                'model' => 'BankTransaction',
                'model_id' => $bankTransaction->id,
                'description' => 'Bank Transaction #' . $bankTransaction->id . ' updated',
                'new_data' => $updatedBankTransaction->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.bank-transactions.index'),
                'message' => 'Bank transaction updated successfully.'
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
    public function destroy(BankTransaction $bankTransaction)
    {
        DB::beginTransaction();

        try {
            $oldData = $bankTransaction->toArray();

            $this->bankTransactionService->delete($bankTransaction);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-transactions',
                'action' => 'delete',
                'model' => 'BankTransaction',
                'model_id' => $oldData['id'],
                'description' => 'Bank Transaction #' . $oldData['id'] . ' deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Bank transaction deleted successfully.'
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

        $model = BankTransaction::find($id);
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
