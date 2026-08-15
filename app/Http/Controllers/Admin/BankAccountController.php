<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankAccountRequest;
use App\Models\BankAccount;
use App\Models\Employee;
use App\Services\BankAccountService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BankAccountController extends Controller
{
    use ActivityLogger;

    protected $bankAccountService;

    public function __construct(BankAccountService $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = BankAccount::query()->with('employee');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('bank_name', 'like', "%{$search}%")
                      ->orWhere('account_number', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.bank-accounts.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('bank_name', function ($row) {
                    $primary = $row->is_primary ? ' <span class="badge bg-success-subtle text-success">Primary</span>' : '';
                    return '<b class="tl-name-txt">' . $row->bank_name . '</b>' . $primary . '<br><small>' . $row->account_number . '</small>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('branch_ifsc', function ($row) {
                    return ($row->branch_name ?? '-') . '<br><small>' . ($row->ifsc_swift_code ?? '-') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.bank-accounts.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'bank_name', 'employee_name', 'branch_ifsc', 'action'])
                ->make(true);
        }

        return view('admin.bank-accounts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.bank-accounts.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BankAccountRequest $request)
    {
        DB::beginTransaction();

        try {
            $bankAccount = $this->bankAccountService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-accounts',
                'action' => 'create',
                'model' => 'BankAccount',
                'model_id' => $bankAccount->id,
                'description' => 'Bank Account "' . $bankAccount->bank_name . '" created',
                'new_data' => $bankAccount->toArray(),
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
    public function edit(BankAccount $bankAccount)
    {
        $employees = Employee::active()->get();

        return view('admin.bank-accounts.edit', compact('bankAccount', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BankAccountRequest $request, BankAccount $bankAccount)
    {
        DB::beginTransaction();

        try {
            $oldData = $bankAccount->toArray();
            $updatedBankAccount = $this->bankAccountService->update($bankAccount, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-accounts',
                'action' => 'update',
                'model' => 'BankAccount',
                'model_id' => $bankAccount->id,
                'description' => 'Bank Account "' . $bankAccount->bank_name . '" updated',
                'new_data' => $updatedBankAccount->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.bank-accounts.index'),
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
    public function destroy(BankAccount $bankAccount)
    {
        DB::beginTransaction();

        try {
            $oldData = $bankAccount->toArray();

            $this->bankAccountService->delete($bankAccount);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'bank-accounts',
                'action' => 'delete',
                'model' => 'BankAccount',
                'model_id' => $oldData['id'],
                'description' => 'Bank Account "' . $oldData['bank_name'] . '" deleted',
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

        $model = BankAccount::find($id);
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
