<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorRequest;
use App\Models\Vendor;
use App\Models\VendorGroup;
use App\Services\VendorService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    use ActivityLogger;

    protected $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Vendor::query()->with('vendorGroup');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by vendor group
            if ($request->vendor_group_id) {
                $query->where('vendor_group_id', $request->vendor_group_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('vendor_code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.vendors.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . $row->vendor_code . '</small>';
                })
                ->addColumn('contact', function ($row) {
                    return ($row->email ?? '-') . '<br><small>' . ($row->phone ?? '-') . '</small>';
                })
                ->addColumn('company_name', function ($row) {
                    return $row->company_name ?? '-';
                })
                ->addColumn('vendor_group_name', function ($row) {
                    return $row->vendorGroup->name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.vendors.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'contact', 'action'])
                ->make(true);
        }

        $vendorGroups = VendorGroup::active()->get();

        return view('admin.vendors.index', compact('vendorGroups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendorGroups = VendorGroup::active()->get();

        return view('admin.vendors.create', compact('vendorGroups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VendorRequest $request)
    {
        DB::beginTransaction();

        try {
            $vendor = $this->vendorService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendors',
                'action' => 'create',
                'model' => 'Vendor',
                'model_id' => $vendor->id,
                'description' => 'Vendor "' . $vendor->name . '" created',
                'new_data' => $vendor->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vendor created successfully.'
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
    public function edit(Vendor $vendor)
    {
        $vendorGroups = VendorGroup::active()->get();

        return view('admin.vendors.edit', compact('vendor', 'vendorGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VendorRequest $request, Vendor $vendor)
    {
        DB::beginTransaction();

        try {
            $oldData = $vendor->toArray();
            $updatedVendor = $this->vendorService->update($vendor, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendors',
                'action' => 'update',
                'model' => 'Vendor',
                'model_id' => $vendor->id,
                'description' => 'Vendor "' . $vendor->name . '" updated',
                'new_data' => $updatedVendor->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.vendors.index'),
                'message' => 'Vendor updated successfully.'
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
    public function destroy(Vendor $vendor)
    {
        DB::beginTransaction();

        try {
            $oldData = $vendor->toArray();

            $this->vendorService->delete($vendor);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendors',
                'action' => 'delete',
                'model' => 'Vendor',
                'model_id' => $oldData['id'],
                'description' => 'Vendor "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vendor deleted successfully.'
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

        $model = Vendor::find($id);
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
