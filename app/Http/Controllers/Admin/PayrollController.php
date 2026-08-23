<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkGeneratePayrollRequest;
use App\Http\Requests\Admin\PayrollRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\EmploymentStatus;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\Shift;
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
            $query = Payroll::query()->with('employee.department', 'employee.designation', 'salaryStructure');

            // Filter by status (only applied when the Advanced Search
            // "Record Status" field is actually set — same "no default
            // status filter, only what the admin explicitly picked"
            // convention Salary Structures/Employees already established
            // once they graduated to the Advanced Search modal).
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by department / designation (resolved through the employee)
            if ($request->department_id) {
                $query->whereHas('employee', function ($eq) use ($request) {
                    $eq->where('department_id', $request->department_id);
                });
            }

            if ($request->designation_id) {
                $query->whereHas('employee', function ($eq) use ($request) {
                    $eq->where('designation_id', $request->designation_id);
                });
            }

            // Filter by pay type (resolved through the salary structure this payroll was generated from)
            if ($request->pay_type) {
                $query->whereHas('salaryStructure', function ($sq) use ($request) {
                    $sq->where('pay_type', $request->pay_type);
                });
            }

            // Filter by reimbursement/payment status
            if ($request->payment_status) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by period (month / year)
            if ($request->filled('month')) {
                $query->where('month', $request->month);
            }

            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            // Filter by net salary range
            if ($request->filled('net_salary_min')) {
                $query->where('net_salary', '>=', $request->net_salary_min);
            }

            if ($request->filled('net_salary_max')) {
                $query->where('net_salary', '<=', $request->net_salary_max);
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
                ->addColumn('period', function ($row) {
                    return \Carbon\Carbon::create($row->year, $row->month, 1)->format('F Y');
                })
                ->addColumn('salary_summary', function ($row) {
                    $payTypeBadge = $row->salaryStructure
                        ? '<span class="badge bg-secondary-subtle text-secondary me-1">' . $row->salaryStructure->pay_type_label . '</span>'
                        : '';

                    $line = $payTypeBadge . 'Net: ' . number_format($row->net_salary, 2) . '<br><small>Earnings: ' . number_format($row->total_earnings, 2) . ' / Deductions: ' . number_format($row->total_deductions, 2) . '</small>';

                    if ($row->overtime_hours > 0) {
                        $line .= '<br><small class="text-warning">OT: ' . number_format($row->overtime_hours, 2) . 'h &rarr; ' . number_format($row->overtime_amount, 2) . '</small>';
                    }

                    if ($row->attendance_deduction > 0) {
                        $line .= '<br><small class="text-danger">Attendance deduction: -' . number_format($row->attendance_deduction, 2) . '</small>';
                    }

                    if ($row->loan_deduction > 0) {
                        $line .= '<br><small class="text-danger">Loan installment: -' . number_format($row->loan_deduction, 2) . '</small>';
                    }

                    return $line;
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

        $employees = Employee::active()->get();
        $departments = Department::active()->get();
        $designations = Designation::active()->get();

        return view('admin.payrolls.index', compact('employees', 'departments', 'designations'));
    }

    /**
     * "How to use" documentation modal.
     */
    public function howTo()
    {
        return view('admin.payrolls.doc');
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
        $activeStructures = SalaryStructure::active()
            ->orderByDesc('effective_date')
            ->with('items.salaryComponent')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($structures) => $structures->first());

        $employeePayTypes = $activeStructures->map(fn ($structure) => $structure->pay_type);

        // Per-occurrence components (e.g. "Late Penalty — $10/occurrence") on
        // each employee's active structure, so the form can render one
        // "how many times this period" input per such component.
        $employeeOccurrenceComponents = $activeStructures->map(function ($structure) {
            return $structure->items
                ->filter(fn ($item) => $item->salaryComponent->calculation_type === 'per_occurrence')
                ->map(fn ($item) => [
                    'id' => $item->salary_component_id,
                    'name' => $item->salaryComponent->name,
                    'rate' => (float) $item->amount,
                ])
                ->values();
        });

        return view('admin.payrolls.create', compact('employees', 'employeePayTypes', 'employeeOccurrenceComponents'));
    }

    /**
     * Show the "Generate for All" bulk form — a month/year plus the same
     * optional narrowing filters (department, designation, shift,
     * employment status, employee type, gender) already offered on the
     * Employees advanced search, so an admin can run payroll for the
     * whole company or for one specific slice of it in a single action.
     */
    public function bulkGenerateForm()
    {
        return view('admin.payrolls.bulk-generate', [
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::with('department')->orderBy('name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
            'employmentStatuses' => EmploymentStatus::orderBy('name')->get(),
            'employeeTypes' => EmployeeType::orderBy('name')->get(),
        ]);
    }

    /**
     * Resolve the employee set from the submitted filters (leaving every
     * filter blank targets every active employee) and hand it to
     * PayrollService::bulkGenerate(). Not wrapped in its own outer
     * DB::beginTransaction() — each employee is generated in its own
     * transaction inside the service, deliberately, so one employee's
     * failure can never roll back payroll already committed for others
     * earlier in the same batch.
     */
    public function bulkGenerate(BulkGeneratePayrollRequest $request)
    {
        $employeeIds = Employee::query()
            ->active()
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('designation_id'), fn ($q) => $q->where('designation_id', $request->designation_id))
            ->when($request->filled('shift_id'), fn ($q) => $q->where('shift_id', $request->shift_id))
            ->when($request->filled('employment_status_id'), fn ($q) => $q->where('employment_status_id', $request->employment_status_id))
            ->when($request->filled('employee_type_id'), fn ($q) => $q->where('employee_type_id', $request->employee_type_id))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->gender))
            ->pluck('id')
            ->all();

        if (empty($employeeIds)) {
            return response()->json([
                'status' => false,
                'message' => 'No active employees match the selected filters.',
            ]);
        }

        try {
            $result = $this->payrollService->bulkGenerate($employeeIds, (int) $request->month, (int) $request->year);

            $created = $result['created'];
            $skipped = $result['skipped'];

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payrolls',
                'action' => 'bulk-generate',
                'description' => 'Bulk payroll generated for ' . $created->count() . ' employee(s) for '
                    . \Carbon\Carbon::create((int) $request->year, (int) $request->month, 1)->format('F Y')
                    . ($skipped->count() ? ', skipped ' . $skipped->count() . ' employee(s)' : ''),
                'new_data' => [
                    'month' => $request->month,
                    'year' => $request->year,
                    'filters' => $request->only([
                        'department_id', 'designation_id', 'shift_id', 'employment_status_id', 'employee_type_id', 'gender',
                    ]),
                    'payroll_ids' => $created->pluck('id')->all(),
                    'skipped' => $skipped->all(),
                ],
                'old_data' => null,
            ]);

            $message = 'Generated payroll for ' . $created->count() . ' employee(s).';

            if ($skipped->count()) {
                $message .= ' Skipped ' . $skipped->count() . ' employee(s) — see Activity Logs for the full list and reasons.';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
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
     * Printable payslip for one payroll run — a standalone page (not the
     * admin layout) meant to be opened in a new tab and saved as a PDF via
     * the browser's own print dialog, the same convention every other
     * print view in this project uses (Delivery Notes, Barcode Generator,
     * POS receipts) rather than adding a server-side PDF library.
     */
    public function payslip(Payroll $payroll)
    {
        $payroll->load([
            'employee.department', 'employee.designation',
            'salaryStructure',
            'items.salaryComponent',
            'loanDeductions.employeeLoan',
        ]);

        $unpaidLeaveDeduction = $this->payrollService->unpaidLeaveDeductionFor($payroll);

        return view('admin.payrolls.payslip', compact('payroll', 'unpaidLeaveDeduction'));
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
