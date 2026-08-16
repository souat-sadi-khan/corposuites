<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExperienceRequest;
use App\Models\Employee;
use App\Models\Experience;
use App\Services\ExperienceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ExperienceController extends Controller
{
    use ActivityLogger;

    protected $experienceService;

    public function __construct(ExperienceService $experienceService)
    {
        $this->experienceService = $experienceService;
    }

    /**
     * Display a modal for how to use the employee experience.
     */
    public function howTo()
    {
        return view('admin.experiences.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Experience::query()->with('employee');

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
                    $q->where('company_name', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%")
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.experiences.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('company_name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->company_name . '</b><br><small>' . $row->designation . '</small>';
                })
                ->addColumn('employee_name', function ($row) {
                    $avatar = Images::show($row->employee->photo);

                    return '
                        <div class="d-flex align-items-center">
                            <div class="mr-2 employee-avatar">
                                ' . $avatar . '
                            </div>
                            <div>
                                <b class="tl-name-txt">' . e($row->employee->full_name) . '</b>
                                <br>
                                <small>' . e($row->employee->employee_code) . '</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('duration', function ($row) {
                    $start = $row->start_date ? $row->start_date->format('M Y') : '-';
                    $end = $row->is_current ? 'Present' : ($row->end_date ? $row->end_date->format('M Y') : '-');
                    return $start . ' - ' . $end;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.experiences.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'company_name', 'employee_name', 'action'])
                ->make(true);
        }

        return view('admin.experiences.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.experiences.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExperienceRequest $request)
    {
        DB::beginTransaction();

        try {
            $experience = $this->experienceService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'experiences',
                'action' => 'create',
                'model' => 'Experience',
                'model_id' => $experience->id,
                'description' => 'Experience "' . $experience->company_name . '" created',
                'new_data' => $experience->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Experience record created successfully.'
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
    public function edit(Experience $experience)
    {
        $employees = Employee::active()->get();

        return view('admin.experiences.edit', compact('experience', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExperienceRequest $request, Experience $experience)
    {
        DB::beginTransaction();

        try {
            $oldData = $experience->toArray();
            $updatedExperience = $this->experienceService->update($experience, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'experiences',
                'action' => 'update',
                'model' => 'Experience',
                'model_id' => $experience->id,
                'description' => 'Experience "' . $experience->company_name . '" updated',
                'new_data' => $updatedExperience->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.experiences.index'),
                'message' => 'Experience record updated successfully.'
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
    public function destroy(Experience $experience)
    {
        DB::beginTransaction();

        try {
            $oldData = $experience->toArray();

            $this->experienceService->delete($experience);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'experiences',
                'action' => 'delete',
                'model' => 'Experience',
                'model_id' => $oldData['id'],
                'description' => 'Experience "' . $oldData['company_name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Experience record deleted successfully.'
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

        $model = Experience::find($id);
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
