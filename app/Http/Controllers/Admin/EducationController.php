<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EducationRequest;
use App\Models\Education;
use App\Models\Employee;
use App\Services\EducationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EducationController extends Controller
{
    use ActivityLogger;

    protected $educationService;

    public function __construct(EducationService $educationService)
    {
        $this->educationService = $educationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Education::query()->with('employee');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('degree', 'like', "%{$search}%")
                      ->orWhere('institution', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.educations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('degree', function ($row) {
                    return '<b class="tl-name-txt">' . $row->degree . '</b><br><small>' . $row->institution . '</small>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('duration', function ($row) {
                    return ($row->start_year ?? '-') . ' - ' . ($row->end_year ?? '-');
                })
                ->addColumn('action', function ($row) {
                    return view('admin.educations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'degree', 'employee_name', 'action'])
                ->make(true);
        }

        return view('admin.educations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.educations.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EducationRequest $request)
    {
        DB::beginTransaction();

        try {
            $education = $this->educationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'educations',
                'action' => 'create',
                'model' => 'Education',
                'model_id' => $education->id,
                'description' => 'Education "' . $education->degree . '" created',
                'new_data' => $education->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Education record created successfully.'
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
    public function edit(Education $education)
    {
        $employees = Employee::active()->get();

        return view('admin.educations.edit', compact('education', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EducationRequest $request, Education $education)
    {
        DB::beginTransaction();

        try {
            $oldData = $education->toArray();
            $updatedEducation = $this->educationService->update($education, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'educations',
                'action' => 'update',
                'model' => 'Education',
                'model_id' => $education->id,
                'description' => 'Education "' . $education->degree . '" updated',
                'new_data' => $updatedEducation->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.educations.index'),
                'message' => 'Education record updated successfully.'
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
    public function destroy(Education $education)
    {
        DB::beginTransaction();

        try {
            $oldData = $education->toArray();

            $this->educationService->delete($education);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'educations',
                'action' => 'delete',
                'model' => 'Education',
                'model_id' => $oldData['id'],
                'description' => 'Education "' . $oldData['degree'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Education record deleted successfully.'
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

        $model = Education::find($id);
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
