<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerGroupRequest;
use App\Models\CustomerGroup;
use App\Services\CustomerGroupService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomerGroupController extends Controller
{
    use ActivityLogger;

    protected $customerGroupService;

    public function __construct(CustomerGroupService $customerGroupService)
    {
        $this->customerGroupService = $customerGroupService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CustomerGroup::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.customer-groups.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->description ?? '') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.customer-groups.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.customer-groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.customer-groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerGroupRequest $request)
    {
        DB::beginTransaction();

        try {
            $customerGroup = $this->customerGroupService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'customer-groups',
                'action' => 'create',
                'model' => 'CustomerGroup',
                'model_id' => $customerGroup->id,
                'description' => 'Customer Group "' . $customerGroup->name . '" created',
                'new_data' => $customerGroup->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Customer group created successfully.'
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
    public function edit(CustomerGroup $customerGroup)
    {
        return view('admin.customer-groups.edit', compact('customerGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerGroupRequest $request, CustomerGroup $customerGroup)
    {
        DB::beginTransaction();

        try {
            $oldData = $customerGroup->toArray();
            $updatedCustomerGroup = $this->customerGroupService->update($customerGroup, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'customer-groups',
                'action' => 'update',
                'model' => 'CustomerGroup',
                'model_id' => $customerGroup->id,
                'description' => 'Customer Group "' . $customerGroup->name . '" updated',
                'new_data' => $updatedCustomerGroup->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.customer-groups.index'),
                'message' => 'Customer group updated successfully.'
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
    public function destroy(CustomerGroup $customerGroup)
    {
        DB::beginTransaction();

        try {
            $oldData = $customerGroup->toArray();

            $this->customerGroupService->delete($customerGroup);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'customer-groups',
                'action' => 'delete',
                'model' => 'CustomerGroup',
                'model_id' => $oldData['id'],
                'description' => 'Customer Group "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Customer group deleted successfully.'
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

        $model = CustomerGroup::find($id);
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
