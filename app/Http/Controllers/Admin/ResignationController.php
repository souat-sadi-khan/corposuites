<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResignationRequest;
use App\Models\Employee;
use App\Models\Resignation;
use App\Services\ResignationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ResignationController extends Controller
{
    use ActivityLogger;

    protected $resignationService;

    public function __construct(ResignationService $resignationService)
    {
        $this->resignationService = $resignationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Resignation::query()->with('employee');

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.resignations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('dates', function ($row) {
                    $resigned = $row->resignation_date ? $row->resignation_date->format('d-m-Y') : '-';
                    $lastDay = $row->last_working_date ? $row->last_working_date->format('d-m-Y') : '-';
                    return 'Resigned: ' . $resigned . '<br><small>Last day: ' . $lastDay . '</small>';
                })
                ->addColumn('notice', function ($row) {
                    return $row->notice_period_days !== null ? $row->notice_period_days . ' days' : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.resignations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'dates', 'action'])
                ->make(true);
        }

        return view('admin.resignations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.resignations.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResignationRequest $request)
    {
        DB::beginTransaction();

        try {
            $resignation = $this->resignationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'resignations',
                'action' => 'create',
                'model' => 'Resignation',
                'model_id' => $resignation->id,
                'description' => 'Resignation recorded for employee #' . $resignation->employee_id,
                'new_data' => $resignation->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Resignation recorded successfully.'
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
    public function edit(Resignation $resignation)
    {
        $employees = Employee::active()->get();

        return view('admin.resignations.edit', compact('resignation', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResignationRequest $request, Resignation $resignation)
    {
        DB::beginTransaction();

        try {
            $oldData = $resignation->toArray();
            $updatedResignation = $this->resignationService->update($resignation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'resignations',
                'action' => 'update',
                'model' => 'Resignation',
                'model_id' => $resignation->id,
                'description' => 'Resignation updated for employee #' . $resignation->employee_id,
                'new_data' => $updatedResignation->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.resignations.index'),
                'message' => 'Resignation updated successfully.'
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
    public function destroy(Resignation $resignation)
    {
        DB::beginTransaction();

        try {
            $oldData = $resignation->toArray();

            $this->resignationService->delete($resignation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'resignations',
                'action' => 'delete',
                'model' => 'Resignation',
                'model_id' => $oldData['id'],
                'description' => 'Resignation deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Resignation deleted successfully.'
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

        $model = Resignation::find($id);
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
