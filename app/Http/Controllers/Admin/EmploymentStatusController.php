<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmploymentStatusRequest;
use App\Models\EmploymentStatus;
use App\Services\EmploymentStatusService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmploymentStatusController extends Controller
{
    use ActivityLogger;

    protected $employmentStatusService;

    public function __construct(EmploymentStatusService $employmentStatusService)
    {
        $this->employmentStatusService = $employmentStatusService;
    }

    /**
     * Display a modal for how to use the employee statuses.
     */
    public function howTo()
    {
        return view('admin.employment-statuses.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmploymentStatus::query();

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.employment-statuses.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_count', function($row) {
                    return $row->employees ? $row->employees->count() : 0;
                })
                ->addColumn('name', function($row) {
                    return '<b class="tl-name-txt">'. $row->name . '</b><br><small>'. $row->description . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.employment-statuses.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.employment-statuses.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.employment-statuses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmploymentStatusRequest $request)
    {
        DB::beginTransaction();

        try {
            $employmentStatus = $this->employmentStatusService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employment-statuses',
                'action' => 'create',
                'model' => 'EmploymentStatus',
                'model_id' => $employmentStatus->id,
                'description' => 'Employment Status "' . $employmentStatus->name . '" created',
                'new_data' => $employmentStatus->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employment status created successfully.'
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
    public function edit(EmploymentStatus $employmentStatus)
    {
        return view('admin.employment-statuses.edit', compact('employmentStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmploymentStatusRequest $request, EmploymentStatus $employmentStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $employmentStatus->toArray();
            $updatedEmploymentStatus = $this->employmentStatusService->update($employmentStatus, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employment-statuses',
                'action' => 'update',
                'model' => 'EmploymentStatus',
                'model_id' => $employmentStatus->id,
                'description' => 'Employment Status "' . $employmentStatus->name . '" updated',
                'new_data' => $updatedEmploymentStatus->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.employment-statuses.index'),
                'message' => 'Employment status updated successfully.'
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
    public function destroy(EmploymentStatus $employmentStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $employmentStatus->toArray();

            $this->employmentStatusService->delete($employmentStatus);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employment-statuses',
                'action' => 'delete',
                'model' => 'EmploymentStatus',
                'model_id' => $oldData['id'],
                'description' => 'Employment Status "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employment status deleted successfully.'
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

        $model = EmploymentStatus::find($id);
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
