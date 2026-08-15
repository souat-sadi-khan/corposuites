<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankReconciliationRequest;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\FinanceBankAccount;
use App\Services\BankReconciliationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BankReconciliationController extends Controller
{
    use ActivityLogger;

    protected $bankReconciliationService;

    public function __construct(BankReconciliationService $bankReconciliationService)
    {
        $this->bankReconciliationService = $bankReconciliationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = BankReconciliation::query()->with('financeBankAccount')->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by reconciliation status
            if ($request->reconciliation_status) {
                $query->where('reconciliation_status', $request->reconciliation_status);
            }

            // Filter by bank account
            if ($request->finance_bank_account_id) {
                $query->where('finance_bank_account_id', $request->finance_bank_account_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reconciliation_number', 'like', "%{$search}%")
                        ->orWhereHas('financeBankAccount', function ($bq) use ($search) {
                            $bq->where('bank_name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.bank-reconciliations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('reconciliation_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->reconciliation_number . '</b><br><small>' . ($row->financeBankAccount->bank_name ?? '-') . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' txn' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('statement_date_formatted', function ($row) {
                    return $row->statement_date ? $row->statement_date->format('d M, Y') : '-';
                })
                ->addColumn('variance_formatted', function ($row) {
                    $color = (float) $row->variance == 0 ? 'success' : 'danger';
                    return '<span class="text-' . $color . '">' . number_format($row->variance, 2) . '</span>';
                })
                ->addColumn('reconciliation_status_badge', function ($row) {
                    $colors = [
                        'draft' => 'secondary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$row->reconciliation_status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->reconciliation_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.bank-reconciliations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'reconciliation_number', 'variance_formatted', 'reconciliation_status_badge', 'action'])
                ->make(true);
        }

        $bankAccounts = FinanceBankAccount::active()->get();

        return view('admin.bank-reconciliations.index', compact('bankAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $bankAccounts = FinanceBankAccount::active()->get();
        $transactions = BankTransaction::active()->where('reconciled', false)->orderBy('transaction_date')->get();

        return view('admin.bank-reconciliations.create', compact('bankAccounts', 'transactions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BankReconciliationRequest $request)
    {
        DB::beginTransaction();

        try {
            $bankReconciliation = $this->bankReconciliationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-reconciliations',
                'action' => 'create',
                'model' => 'BankReconciliation',
                'model_id' => $bankReconciliation->id,
                'description' => 'Bank Reconciliation "' . $bankReconciliation->reconciliation_number . '" created',
                'new_data' => $bankReconciliation->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Bank reconciliation created successfully.'
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
    public function edit(BankReconciliation $bankReconciliation)
    {
        $bankAccounts = FinanceBankAccount::active()->get();

        // Unreconciled transactions plus whatever this reconciliation already claimed,
        // so editing doesn't lose the currently-selected transactions from the list.
        $existingTransactionIds = $bankReconciliation->items()->pluck('bank_transaction_id');
        $transactions = BankTransaction::active()
            ->where(function ($q) use ($existingTransactionIds) {
                $q->where('reconciled', false)->orWhereIn('id', $existingTransactionIds);
            })
            ->orderBy('transaction_date')
            ->get();

        $bankReconciliation->load('items');

        return view('admin.bank-reconciliations.edit', compact('bankReconciliation', 'bankAccounts', 'transactions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BankReconciliationRequest $request, BankReconciliation $bankReconciliation)
    {
        DB::beginTransaction();

        try {
            $oldData = $bankReconciliation->load('items')->toArray();
            $updatedBankReconciliation = $this->bankReconciliationService->update($bankReconciliation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-reconciliations',
                'action' => 'update',
                'model' => 'BankReconciliation',
                'model_id' => $bankReconciliation->id,
                'description' => 'Bank Reconciliation "' . $bankReconciliation->reconciliation_number . '" updated',
                'new_data' => $updatedBankReconciliation->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.bank-reconciliations.index'),
                'message' => 'Bank reconciliation updated successfully.'
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
    public function destroy(BankReconciliation $bankReconciliation)
    {
        DB::beginTransaction();

        try {
            $oldData = $bankReconciliation->load('items')->toArray();

            $this->bankReconciliationService->delete($bankReconciliation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-reconciliations',
                'action' => 'delete',
                'model' => 'BankReconciliation',
                'model_id' => $oldData['id'],
                'description' => 'Bank Reconciliation "' . $oldData['reconciliation_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Bank reconciliation deleted successfully.'
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

        $model = BankReconciliation::find($id);
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
