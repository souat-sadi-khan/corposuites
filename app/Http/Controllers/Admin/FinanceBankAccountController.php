<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinanceBankAccountRequest;
use App\Models\ChartOfAccount;
use App\Models\FinanceBankAccount;
use App\Services\FinanceBankAccountService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FinanceBankAccountController extends Controller
{
    use ActivityLogger;

    protected $financeBankAccountService;

    public function __construct(FinanceBankAccountService $financeBankAccountService)
    {
        $this->financeBankAccountService = $financeBankAccountService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FinanceBankAccount::query()->with('chartOfAccount');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.finance-bank-accounts.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('bank_name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->bank_name . '</b><br><small>' . $row->account_number . '</small>';
                })
                ->addColumn('linked_account', function ($row) {
                    return $row->chartOfAccount ? $row->chartOfAccount->code . ' - ' . $row->chartOfAccount->name : '-';
                })
                ->addColumn('opening_balance_formatted', function ($row) {
                    return number_format($row->opening_balance, 2) . ' ' . $row->currency;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.finance-bank-accounts.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'bank_name', 'action'])
                ->make(true);
        }

        return view('admin.finance-bank-accounts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $chartOfAccounts = ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get();

        return view('admin.finance-bank-accounts.create', compact('chartOfAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FinanceBankAccountRequest $request)
    {
        DB::beginTransaction();

        try {
            $financeBankAccount = $this->financeBankAccountService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'finance-bank-accounts',
                'action' => 'create',
                'model' => 'FinanceBankAccount',
                'model_id' => $financeBankAccount->id,
                'description' => 'Bank Account "' . $financeBankAccount->bank_name . ' - ' . $financeBankAccount->account_number . '" created',
                'new_data' => $financeBankAccount->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Bank account created successfully.'
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
    public function edit(FinanceBankAccount $financeBankAccount)
    {
        $chartOfAccounts = ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get();

        return view('admin.finance-bank-accounts.edit', compact('financeBankAccount', 'chartOfAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinanceBankAccountRequest $request, FinanceBankAccount $financeBankAccount)
    {
        DB::beginTransaction();

        try {
            $oldData = $financeBankAccount->toArray();
            $updatedFinanceBankAccount = $this->financeBankAccountService->update($financeBankAccount, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'finance-bank-accounts',
                'action' => 'update',
                'model' => 'FinanceBankAccount',
                'model_id' => $financeBankAccount->id,
                'description' => 'Bank Account "' . $updatedFinanceBankAccount->bank_name . ' - ' . $updatedFinanceBankAccount->account_number . '" updated',
                'new_data' => $updatedFinanceBankAccount->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.finance-bank-accounts.index'),
                'message' => 'Bank account updated successfully.'
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
    public function destroy(FinanceBankAccount $financeBankAccount)
    {
        DB::beginTransaction();

        try {
            $oldData = $financeBankAccount->toArray();

            $this->financeBankAccountService->delete($financeBankAccount);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'finance-bank-accounts',
                'action' => 'delete',
                'model' => 'FinanceBankAccount',
                'model_id' => $oldData['id'],
                'description' => 'Bank Account "' . $oldData['bank_name'] . ' - ' . $oldData['account_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Bank account deleted successfully.'
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

        $model = FinanceBankAccount::find($id);
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
