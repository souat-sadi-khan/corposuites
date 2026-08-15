<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeTypeRequest;
use App\Models\EmployeeType;
use App\Services\EmployeeTypeService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeTypeController extends Controller
{
    use ActivityLogger;

    protected $employeeTypeService;

    public function __construct(EmployeeTypeService $employeeTypeService)
    {
        $this->employeeTypeService = $employeeTypeService;
    }

    /**
     * Display a modal of docs for how to use EMPLOYEE TYPES.
     */
    public function howTo()
    {
        return view('admin.employee-types.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeType::query();

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.employee-types.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_count', function($row) {
                    return $row->employees->count();
                })
                ->addColumn('name', function($row) {
                    return '<b class="tl-name-txt">'. $row->name . '</b><br><small>'. $row->description . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.employee-types.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.employee-types.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.employee-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeTypeRequest $request)
    {
        DB::beginTransaction();

        try {
            $employeeType = $this->employeeTypeService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-types',
                'action' => 'create',
                'model' => 'EmployeeType',
                'model_id' => $employeeType->id,
                'description' => 'Employee Type "' . $employeeType->name . '" created',
                'new_data' => $employeeType->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee type created successfully.'
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
    public function edit(EmployeeType $employeeType)
    {
        return view('admin.employee-types.edit', compact('employeeType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeTypeRequest $request, EmployeeType $employeeType)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeType->toArray();
            $updatedEmployeeType = $this->employeeTypeService->update($employeeType, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-types',
                'action' => 'update',
                'model' => 'EmployeeType',
                'model_id' => $employeeType->id,
                'description' => 'Employee Type "' . $employeeType->name . '" updated',
                'new_data' => $updatedEmployeeType->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.employee-types.index'),
                'message' => 'Employee type updated successfully.'
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
    public function destroy(EmployeeType $employeeType)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeType->toArray();

            $this->employeeTypeService->delete($employeeType);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-types',
                'action' => 'delete',
                'model' => 'EmployeeType',
                'model_id' => $oldData['id'],
                'description' => 'Employee Type "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee type deleted successfully.'
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

        $model = EmployeeType::find($id);
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
