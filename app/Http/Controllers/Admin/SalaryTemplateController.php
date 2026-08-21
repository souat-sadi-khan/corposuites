<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryTemplateAssignRequest;
use App\Http\Requests\Admin\SalaryTemplateRequest;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\SalaryTemplate;
use App\Services\SalaryTemplateService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalaryTemplateController extends Controller
{
    use ActivityLogger;

    protected $salaryTemplateService;

    public function __construct(SalaryTemplateService $salaryTemplateService)
    {
        $this->salaryTemplateService = $salaryTemplateService;
    }

    /**
     * "How to use" documentation modal.
     */
    public function howTo()
    {
        return view('admin.salary-templates.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalaryTemplate::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by pay type
            if ($request->pay_type) {
                $query->where('pay_type', $request->pay_type);
            }

            // Search
            if ($request->search) {
                $query->where('name', 'like', "%{$request->search}%");
            }

            $query->orderBy('name');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.salary-templates.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('pay_type_badge', function ($row) {
                    $map = ['monthly' => 'primary', 'daily' => 'info', 'commission' => 'warning'];
                    $color = $map[$row->pay_type] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . $row->pay_type_label . '</span>';
                })
                ->addColumn('salary_summary', function ($row) {
                    $label = match ($row->pay_type) {
                        'daily' => 'Rate',
                        'commission' => 'Rate (%)',
                        default => 'Basic',
                    };

                    return $label . ': ' . number_format($row->basic_salary, 2) . '<br><small>Gross: ' . number_format($row->gross_salary, 2) . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.salary-templates.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'pay_type_badge', 'salary_summary', 'action'])
                ->make(true);
        }

        return view('admin.salary-templates.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $salaryComponents = SalaryComponent::active()->get();

        return view('admin.salary-templates.create', compact('salaryComponents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalaryTemplateRequest $request)
    {
        DB::beginTransaction();

        try {
            $salaryTemplate = $this->salaryTemplateService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-templates',
                'action' => 'create',
                'model' => 'SalaryTemplate',
                'model_id' => $salaryTemplate->id,
                'description' => 'Salary template created: ' . $salaryTemplate->name,
                'new_data' => $salaryTemplate->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Salary template created successfully.'
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
    public function edit(SalaryTemplate $salaryTemplate)
    {
        $salaryComponents = SalaryComponent::active()->get();
        $salaryTemplate->load('items');

        return view('admin.salary-templates.edit', compact('salaryTemplate', 'salaryComponents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalaryTemplateRequest $request, SalaryTemplate $salaryTemplate)
    {
        DB::beginTransaction();

        try {
            $oldData = $salaryTemplate->load('items')->toArray();
            $updatedSalaryTemplate = $this->salaryTemplateService->update($salaryTemplate, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-templates',
                'action' => 'update',
                'model' => 'SalaryTemplate',
                'model_id' => $salaryTemplate->id,
                'description' => 'Salary template updated: ' . $salaryTemplate->name,
                'new_data' => $updatedSalaryTemplate->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.salary-templates.index'),
                'message' => 'Salary template updated successfully.'
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
    public function destroy(SalaryTemplate $salaryTemplate)
    {
        DB::beginTransaction();

        try {
            $oldData = $salaryTemplate->load('items')->toArray();

            $this->salaryTemplateService->delete($salaryTemplate);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-templates',
                'action' => 'delete',
                'model' => 'SalaryTemplate',
                'model_id' => $oldData['id'],
                'description' => 'Salary template deleted: ' . $oldData['name'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Salary template deleted successfully.'
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

        $model = SalaryTemplate::find($id);
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

    /**
     * Show the "Assign to Employees" bulk-assignment form.
     */
    public function assignForm(SalaryTemplate $salaryTemplate)
    {
        $salaryTemplate->load('items.salaryComponent');
        $employees = Employee::active()->get();

        return view('admin.salary-templates.assign', compact('salaryTemplate', 'employees'));
    }

    /**
     * Apply this template — its pay type, rate, and components — as a brand
     * new Salary Structure for every selected employee.
     */
    public function assign(SalaryTemplateAssignRequest $request, SalaryTemplate $salaryTemplate)
    {
        DB::beginTransaction();

        try {
            $salaryTemplate->load('items');

            $result = $this->salaryTemplateService->assignToEmployees(
                $salaryTemplate,
                $request->validated('employee_ids'),
                $request->validated('effective_date'),
                (bool) $request->validated('status')
            );

            $created = $result['created'];
            $skipped = $result['skipped'];

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-templates',
                'action' => 'assign',
                'model' => 'SalaryTemplate',
                'model_id' => $salaryTemplate->id,
                'description' => 'Salary template "' . $salaryTemplate->name . '" applied to ' . $created->count() . ' employee(s)'
                    . ($skipped->count() ? ', skipped ' . $skipped->count() . ' below the configured minimum wage' : ''),
                'new_data' => [
                    'employee_ids' => $request->validated('employee_ids'),
                    'salary_structure_ids' => $created->pluck('id')->all(),
                    'skipped' => $skipped->all(),
                ],
                'old_data' => null
            ]);

            DB::commit();

            $message = 'Template applied to ' . $created->count() . ' employee(s) successfully.';

            if ($skipped->count()) {
                $message .= ' Skipped ' . $skipped->count() . ' employee(s) below the configured minimum wage for their location.';
            }

            return response()->json([
                'status' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
