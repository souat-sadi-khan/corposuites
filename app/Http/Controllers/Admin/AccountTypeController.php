<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountTypeRequest;
use App\Models\AccountType;
use App\Services\AccountTypeService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AccountTypeController extends Controller
{
    use ActivityLogger;

    protected $accountTypeService;

    public function __construct(AccountTypeService $accountTypeService)
    {
        $this->accountTypeService = $accountTypeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AccountType::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by nature
            if ($request->nature) {
                $query->where('nature', $request->nature);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.account-types.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('nature_badge', function ($row) {
                    $colors = [
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'purple',
                        'revenue' => 'info',
                        'expense' => 'warning',
                    ];
                    $color = $colors[$row->nature] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->nature) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.account-types.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'nature_badge', 'action'])
                ->make(true);
        }

        return view('admin.account-types.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.account-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountTypeRequest $request)
    {
        DB::beginTransaction();

        try {
            $accountType = $this->accountTypeService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'account-types',
                'action' => 'create',
                'model' => 'AccountType',
                'model_id' => $accountType->id,
                'description' => 'Account Type "' . $accountType->name . '" created',
                'new_data' => $accountType->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Account type created successfully.'
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
    public function edit(AccountType $accountType)
    {
        return view('admin.account-types.edit', compact('accountType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountTypeRequest $request, AccountType $accountType)
    {
        DB::beginTransaction();

        try {
            $oldData = $accountType->toArray();
            $updatedAccountType = $this->accountTypeService->update($accountType, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'account-types',
                'action' => 'update',
                'model' => 'AccountType',
                'model_id' => $accountType->id,
                'description' => 'Account Type "' . $updatedAccountType->name . '" updated',
                'new_data' => $updatedAccountType->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.account-types.index'),
                'message' => 'Account type updated successfully.'
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
    public function destroy(AccountType $accountType)
    {
        DB::beginTransaction();

        try {
            $oldData = $accountType->toArray();

            $this->accountTypeService->delete($accountType);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'account-types',
                'action' => 'delete',
                'model' => 'AccountType',
                'model_id' => $oldData['id'],
                'description' => 'Account Type "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Account type deleted successfully.'
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

        $model = AccountType::find($id);
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
