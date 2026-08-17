<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TerminationRequest;
use App\Models\Employee;
use App\Models\Termination;
use App\Services\TerminationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TerminationController extends Controller
{
    use ActivityLogger;

    protected $terminationService;

    public function __construct(TerminationService $terminationService)
    {
        $this->terminationService = $terminationService;
    }

    /**
     * Display a modal for how to use the employee termination.
     */
    public function howTo()
    {
        return view('admin.terminations.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Termination::query()->with('employee');

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
                    $q->where('reason', 'like', "%{$search}%")
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.terminations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
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
                ->addColumn('type_badge', function ($row) {
                    return $row->type === 'voluntary'
                        ? '<span class="badge bg-warning-subtle text-warning">Voluntary</span>'
                        : '<span class="badge bg-danger-subtle text-danger">Involuntary</span>';
                })
                ->addColumn('termination_date_formatted', function ($row) {
                    return $row->termination_date ? $row->termination_date->format('d-m-Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.terminations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'type_badge', 'action'])
                ->make(true);
        }

        return view('admin.terminations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.terminations.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TerminationRequest $request)
    {
        DB::beginTransaction();

        try {
            $termination = $this->terminationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'terminations',
                'action' => 'create',
                'model' => 'Termination',
                'model_id' => $termination->id,
                'description' => 'Termination recorded for employee #' . $termination->employee_id,
                'new_data' => $termination->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Termination recorded successfully.'
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
    public function edit(Termination $termination)
    {
        $employees = Employee::active()->get();

        return view('admin.terminations.edit', compact('termination', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TerminationRequest $request, Termination $termination)
    {
        DB::beginTransaction();

        try {
            $oldData = $termination->toArray();
            $updatedTermination = $this->terminationService->update($termination, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'terminations',
                'action' => 'update',
                'model' => 'Termination',
                'model_id' => $termination->id,
                'description' => 'Termination updated for employee #' . $termination->employee_id,
                'new_data' => $updatedTermination->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.terminations.index'),
                'message' => 'Termination updated successfully.'
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
    public function destroy(Termination $termination)
    {
        DB::beginTransaction();

        try {
            $oldData = $termination->toArray();

            $this->terminationService->delete($termination);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'terminations',
                'action' => 'delete',
                'model' => 'Termination',
                'model_id' => $oldData['id'],
                'description' => 'Termination deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Termination deleted successfully.'
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

        $model = Termination::find($id);
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
