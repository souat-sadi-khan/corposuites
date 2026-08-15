<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorGroupRequest;
use App\Models\VendorGroup;
use App\Services\VendorGroupService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class VendorGroupController extends Controller
{
    use ActivityLogger;

    protected $vendorGroupService;

    public function __construct(VendorGroupService $vendorGroupService)
    {
        $this->vendorGroupService = $vendorGroupService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = VendorGroup::query();

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.vendor-groups.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->description ?? '') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.vendor-groups.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.vendor-groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.vendor-groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VendorGroupRequest $request)
    {
        DB::beginTransaction();

        try {
            $vendorGroup = $this->vendorGroupService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendor-groups',
                'action' => 'create',
                'model' => 'VendorGroup',
                'model_id' => $vendorGroup->id,
                'description' => 'Vendor Group "' . $vendorGroup->name . '" created',
                'new_data' => $vendorGroup->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vendor group created successfully.'
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
    public function edit(VendorGroup $vendorGroup)
    {
        return view('admin.vendor-groups.edit', compact('vendorGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VendorGroupRequest $request, VendorGroup $vendorGroup)
    {
        DB::beginTransaction();

        try {
            $oldData = $vendorGroup->toArray();
            $updatedVendorGroup = $this->vendorGroupService->update($vendorGroup, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendor-groups',
                'action' => 'update',
                'model' => 'VendorGroup',
                'model_id' => $vendorGroup->id,
                'description' => 'Vendor Group "' . $vendorGroup->name . '" updated',
                'new_data' => $updatedVendorGroup->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.vendor-groups.index'),
                'message' => 'Vendor group updated successfully.'
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
    public function destroy(VendorGroup $vendorGroup)
    {
        DB::beginTransaction();

        try {
            $oldData = $vendorGroup->toArray();

            $this->vendorGroupService->delete($vendorGroup);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendor-groups',
                'action' => 'delete',
                'model' => 'VendorGroup',
                'model_id' => $oldData['id'],
                'description' => 'Vendor Group "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vendor group deleted successfully.'
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

        $model = VendorGroup::find($id);
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
