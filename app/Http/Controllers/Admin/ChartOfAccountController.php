<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChartOfAccountRequest;
use App\Models\AccountType;
use App\Models\ChartOfAccount;
use App\Services\ChartOfAccountService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartOfAccountController extends Controller
{
    use ActivityLogger;

    protected $chartOfAccountService;

    public function __construct(ChartOfAccountService $chartOfAccountService)
    {
        $this->chartOfAccountService = $chartOfAccountService;
    }

    /**
     * Display the chart of accounts tree.
     */
    public function index(Request $request)
    {
        $accounts = ChartOfAccount::with('accountType')->orderBy('code')->get();

        return view('admin.chart-of-accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        $selectedParentId = $request->query('parent_id');
        $accountTypes = AccountType::active()->orderBy('name')->get();

        return view('admin.chart-of-accounts.create', compact('accounts', 'selectedParentId', 'accountTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ChartOfAccountRequest $request)
    {
        DB::beginTransaction();

        try {
            $chartOfAccount = $this->chartOfAccountService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'chart-of-accounts',
                'action' => 'create',
                'model' => 'ChartOfAccount',
                'model_id' => $chartOfAccount->id,
                'description' => 'Account "' . $chartOfAccount->code . ' - ' . $chartOfAccount->name . '" created',
                'new_data' => $chartOfAccount->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Account created successfully.'
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
    public function edit(ChartOfAccount $chartOfAccount)
    {
        $excludedIds = array_merge([$chartOfAccount->id], $chartOfAccount->descendantIds());
        $accounts = ChartOfAccount::orderBy('code')->get()->whereNotIn('id', $excludedIds);
        $accountTypes = AccountType::active()->orderBy('name')->get();

        return view('admin.chart-of-accounts.edit', compact('chartOfAccount', 'accounts', 'accountTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ChartOfAccountRequest $request, ChartOfAccount $chartOfAccount)
    {
        DB::beginTransaction();

        try {
            $oldData = $chartOfAccount->toArray();
            $updatedChartOfAccount = $this->chartOfAccountService->update($chartOfAccount, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'chart-of-accounts',
                'action' => 'update',
                'model' => 'ChartOfAccount',
                'model_id' => $chartOfAccount->id,
                'description' => 'Account "' . $updatedChartOfAccount->code . ' - ' . $updatedChartOfAccount->name . '" updated',
                'new_data' => $updatedChartOfAccount->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.chart-of-accounts.index'),
                'message' => 'Account updated successfully.'
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
    public function destroy(ChartOfAccount $chartOfAccount)
    {
        DB::beginTransaction();

        try {
            $oldData = $chartOfAccount->toArray();

            $this->chartOfAccountService->delete($chartOfAccount);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'chart-of-accounts',
                'action' => 'delete',
                'model' => 'ChartOfAccount',
                'model_id' => $oldData['id'],
                'description' => 'Account "' . $oldData['code'] . ' - ' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Account deleted successfully.'
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

        $model = ChartOfAccount::find($id);
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
