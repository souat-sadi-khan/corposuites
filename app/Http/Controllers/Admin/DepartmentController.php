<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    use ActivityLogger;

    protected $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    /**
     * Display a modal for how to use the employee statuses.
     */
    public function howTo()
    {
        return view('admin.departments.doc');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Department::query();

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.departments.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employees', function($row) {
                    return $row->employees ? $row->employees->count() : 0;
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->description ?? '') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.departments.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.departments.index');
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(DepartmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $department = $this->departmentService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'departments',
                'action' => 'create',
                'model' => 'Department',
                'model_id' => $department->id,
                'description' => 'Department "' . $department->name . '" created',
                'new_data' => $department->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Department created successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        DB::beginTransaction();

        try {
            $oldData = $department->toArray();
            $updatedDepartment = $this->departmentService->update($department, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'departments',
                'action' => 'update',
                'model' => 'Department',
                'model_id' => $department->id,
                'description' => 'Department "' . $department->name . '" updated',
                'new_data' => $updatedDepartment->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json(['status' => true, 'goto' => route('admin.departments.index'), 'message' => 'Department updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Department $department)
    {
        DB::beginTransaction();

        try {
            $oldData = $department->toArray();
            $this->departmentService->delete($department);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'departments',
                'action' => 'delete',
                'model' => 'Department',
                'model_id' => $oldData['id'],
                'description' => 'Department "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Department deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|boolean']);

        $model = Department::find($id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Record not found.']);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json(['success' => true, 'message' => 'Record status updated successfully.']);
    }
}
