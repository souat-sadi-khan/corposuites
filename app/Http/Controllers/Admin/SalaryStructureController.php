<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryStructureRequest;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Services\SalaryStructureService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalaryStructureController extends Controller
{
    use ActivityLogger;

    protected $salaryStructureService;

    public function __construct(SalaryStructureService $salaryStructureService)
    {
        $this->salaryStructureService = $salaryStructureService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalaryStructure::query()->with('employee');

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
                $query->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            $query->orderBy('effective_date', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.salary-structures.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('effective_date_formatted', function ($row) {
                    return $row->effective_date ? $row->effective_date->format('d-m-Y') : '-';
                })
                ->addColumn('salary_summary', function ($row) {
                    return 'Basic: ' . number_format($row->basic_salary, 2) . '<br><small>Gross: ' . number_format($row->gross_salary, 2) . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.salary-structures.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'salary_summary', 'action'])
                ->make(true);
        }

        return view('admin.salary-structures.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();
        $salaryComponents = SalaryComponent::active()->get();

        return view('admin.salary-structures.create', compact('employees', 'salaryComponents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalaryStructureRequest $request)
    {
        DB::beginTransaction();

        try {
            $salaryStructure = $this->salaryStructureService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-structures',
                'action' => 'create',
                'model' => 'SalaryStructure',
                'model_id' => $salaryStructure->id,
                'description' => 'Salary structure created for employee #' . $salaryStructure->employee_id,
                'new_data' => $salaryStructure->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Salary structure created successfully.'
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
    public function edit(SalaryStructure $salaryStructure)
    {
        $employees = Employee::active()->get();
        $salaryComponents = SalaryComponent::active()->get();
        $salaryStructure->load('items');

        return view('admin.salary-structures.edit', compact('salaryStructure', 'employees', 'salaryComponents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalaryStructureRequest $request, SalaryStructure $salaryStructure)
    {
        DB::beginTransaction();

        try {
            $oldData = $salaryStructure->load('items')->toArray();
            $updatedSalaryStructure = $this->salaryStructureService->update($salaryStructure, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-structures',
                'action' => 'update',
                'model' => 'SalaryStructure',
                'model_id' => $salaryStructure->id,
                'description' => 'Salary structure updated for employee #' . $salaryStructure->employee_id,
                'new_data' => $updatedSalaryStructure->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.salary-structures.index'),
                'message' => 'Salary structure updated successfully.'
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
    public function destroy(SalaryStructure $salaryStructure)
    {
        DB::beginTransaction();

        try {
            $oldData = $salaryStructure->load('items')->toArray();

            $this->salaryStructureService->delete($salaryStructure);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-structures',
                'action' => 'delete',
                'model' => 'SalaryStructure',
                'model_id' => $oldData['id'],
                'description' => 'Salary structure deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Salary structure deleted successfully.'
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

        $model = SalaryStructure::find($id);
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
