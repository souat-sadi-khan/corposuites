<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayrollRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Services\PayrollService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PayrollController extends Controller
{
    use ActivityLogger;

    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Payroll::query()->with('employee', 'salaryStructure');

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

            $query->orderBy('year', 'DESC')->orderBy('month', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.payrolls.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('period', function ($row) {
                    return \Carbon\Carbon::create($row->year, $row->month, 1)->format('F Y');
                })
                ->addColumn('salary_summary', function ($row) {
                    $payTypeBadge = $row->salaryStructure
                        ? '<span class="badge bg-secondary-subtle text-secondary me-1">' . $row->salaryStructure->pay_type_label . '</span>'
                        : '';

                    return $payTypeBadge . 'Net: ' . number_format($row->net_salary, 2) . '<br><small>Earnings: ' . number_format($row->total_earnings, 2) . ' / Deductions: ' . number_format($row->total_deductions, 2) . '</small>';
                })
                ->addColumn('payment_badge', function ($row) {
                    return $row->payment_status === 'paid'
                        ? '<span class="badge bg-success-subtle text-success">Paid</span>'
                        : '<span class="badge bg-warning-subtle text-warning">Unpaid</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.payrolls.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'salary_summary', 'payment_badge', 'action'])
                ->make(true);
        }

        return view('admin.payrolls.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        // The latest active salary structure per employee, keyed by employee_id,
        // so the form can show/require the Sales Amount field only for employees
        // on a commission-based structure — same "active, latest effective_date"
        // resolution PayrollService::create() uses to pick the structure.
        $employeePayTypes = SalaryStructure::active()
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($structures) => $structures->first()->pay_type);

        return view('admin.payrolls.create', compact('employees', 'employeePayTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PayrollRequest $request)
    {
        DB::beginTransaction();

        try {
            $payroll = $this->payrollService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payrolls',
                'action' => 'create',
                'model' => 'Payroll',
                'model_id' => $payroll->id,
                'description' => 'Payroll generated for employee #' . $payroll->employee_id,
                'new_data' => $payroll->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payroll generated successfully.'
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
    public function destroy(Payroll $payroll)
    {
        DB::beginTransaction();

        try {
            $oldData = $payroll->load('items')->toArray();

            $this->payrollService->delete($payroll);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payrolls',
                'action' => 'delete',
                'model' => 'Payroll',
                'model_id' => $oldData['id'],
                'description' => 'Payroll deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payroll deleted successfully.'
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
     * Mark the payroll as paid.
     */
    public function markAsPaid(Payroll $payroll)
    {
        DB::beginTransaction();

        try {
            $oldData = $payroll->toArray();
            $this->payrollService->markAsPaid($payroll);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payrolls',
                'action' => 'mark-paid',
                'model' => 'Payroll',
                'model_id' => $payroll->id,
                'description' => 'Payroll marked as paid for employee #' . $payroll->employee_id,
                'new_data' => $payroll->fresh()->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payroll marked as paid.'
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

        $model = Payroll::find($id);
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
