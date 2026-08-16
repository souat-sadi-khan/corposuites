<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DesignationRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Services\DesignationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DesignationController extends Controller
{
    use ActivityLogger;

    protected $designationService;

    public function __construct(DesignationService $designationService)
    {
        $this->designationService = $designationService;
    }

    /**
     * Display a modal for how to use the employee department.
     */
    public function howTo()
    {
        return view('admin.designations.doc');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Designation::query()->with('department');

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('department', function ($dq) use ($search) {
                          $dq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.designations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employees', function($row) {
                    return $row->employees ? $row->employees->count() : 0;
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small> Department: ' . ($row->department->name ?? 'No Department') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.designations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.designations.index');
    }

    public function create()
    {
        $departments = Department::active()->get();

        return view('admin.designations.create', compact('departments'));
    }

    public function store(DesignationRequest $request)
    {
        DB::beginTransaction();

        try {
            $designation = $this->designationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'designations',
                'action' => 'create',
                'model' => 'Designation',
                'model_id' => $designation->id,
                'description' => 'Designation "' . $designation->name . '" created',
                'new_data' => $designation->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Designation created successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function edit(Designation $designation)
    {
        $departments = Department::active()->get();

        return view('admin.designations.edit', compact('designation', 'departments'));
    }

    public function update(DesignationRequest $request, Designation $designation)
    {
        DB::beginTransaction();

        try {
            $oldData = $designation->toArray();
            $updatedDesignation = $this->designationService->update($designation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'designations',
                'action' => 'update',
                'model' => 'Designation',
                'model_id' => $designation->id,
                'description' => 'Designation "' . $designation->name . '" updated',
                'new_data' => $updatedDesignation->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json(['status' => true, 'goto' => route('admin.designations.index'), 'message' => 'Designation updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Designation $designation)
    {
        DB::beginTransaction();

        try {
            $oldData = $designation->toArray();
            $this->designationService->delete($designation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'designations',
                'action' => 'delete',
                'model' => 'Designation',
                'model_id' => $oldData['id'],
                'description' => 'Designation "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Designation deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|boolean']);

        $model = Designation::find($id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Record not found.']);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json(['success' => true, 'message' => 'Record status updated successfully.']);
    }
}
