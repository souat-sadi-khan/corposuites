<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveTypeRequest;
use App\Models\Designation;
use App\Models\EmployeeType;
use App\Models\LeaveType;
use App\Services\LeaveTypeService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveTypeController extends Controller
{
    use ActivityLogger;

    protected $leaveTypeService;

    public function __construct(LeaveTypeService $leaveTypeService)
    {
        $this->leaveTypeService = $leaveTypeService;
    }

    /**
     * Display a modal for how to use the employee leave type.
     */
    public function howTo()
    {
        return view('admin.leave-types.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeaveType::query();

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.leave-types.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->description ?? '') . '</small>';
                })
                ->addColumn('paid_badge', function ($row) {
                    return $row->is_paid
                        ? '<span class="badge bg-success-subtle text-success">Paid</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary">Unpaid</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.leave-types.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'paid_badge', 'action'])
                ->make(true);
        }

        return view('admin.leave-types.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employeeTypes = EmployeeType::where('status', 1)->orderBy('name')->get();
        $designations = Designation::where('status', 1)->orderBy('name')->get();

        return view('admin.leave-types.create', compact('employeeTypes', 'designations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeaveTypeRequest $request)
    {
        DB::beginTransaction();

        try {
            $leaveType = $this->leaveTypeService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-types',
                'action' => 'create',
                'model' => 'LeaveType',
                'model_id' => $leaveType->id,
                'description' => 'Leave Type "' . $leaveType->name . '" created',
                'new_data' => $leaveType->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave type created successfully.'
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
    public function edit(LeaveType $leaveType)
    {
        $employeeTypes = EmployeeType::where('status', 1)->orderBy('name')->get();
        $designations = Designation::where('status', 1)->orderBy('name')->get();

        return view('admin.leave-types.edit', compact('leaveType', 'employeeTypes', 'designations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeaveTypeRequest $request, LeaveType $leaveType)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveType->toArray();
            $updatedLeaveType = $this->leaveTypeService->update($leaveType, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-types',
                'action' => 'update',
                'model' => 'LeaveType',
                'model_id' => $leaveType->id,
                'description' => 'Leave Type "' . $leaveType->name . '" updated',
                'new_data' => $updatedLeaveType->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.leave-types.index'),
                'message' => 'Leave type updated successfully.'
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
    public function destroy(LeaveType $leaveType)
    {
        DB::beginTransaction();

        try {
            $oldData = $leaveType->toArray();

            $this->leaveTypeService->delete($leaveType);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leave-types',
                'action' => 'delete',
                'model' => 'LeaveType',
                'model_id' => $oldData['id'],
                'description' => 'Leave Type "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Leave type deleted successfully.'
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

        $model = LeaveType::find($id);
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
