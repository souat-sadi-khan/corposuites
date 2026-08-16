<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\EmploymentStatus;
use App\Models\Shift;
use App\Services\EmployeeService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    use ActivityLogger;

    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Employee::query()->with(['employeeType', 'employmentStatus', 'admin']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.employees.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    $avatar = Images::show($row->photo);
                    $roleName = 'Unassigned';
                    if($row->admin) {
                        $roleName = $row->admin->roles->pluck('name')->implode(', ');
                    }

                    return '
                        <div class="d-flex align-items-center">
                            <div class="mr-2 employee-avatar">
                                ' . $avatar . '
                            </div>
                            <div>
                                <b class="tl-name-txt">' . e($row->full_name) . '</b>
                                <br>
                                <small>' . e($row->employee_code) . '</small> | 
                                <small>Role: '. $roleName .'</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('contact', function ($row) {
                    return $row->email . '<br><small>' . ($row->phone ?? '-') . '</small>';
                })
                ->addColumn('type_status', function ($row) {
                    return ($row->employeeType->name ?? '-') . '<br><small>' . ($row->employmentStatus->name ?? '-') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.employees.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'contact', 'type_status', 'action'])
                ->make(true);
        }

        return view('admin.employees.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employeeTypes = EmployeeType::active()->get();
        $employmentStatuses = EmploymentStatus::active()->get();
        $shifts = Shift::active()->get();
        $departments = Department::active()->get();
        $designations = Designation::active()->get();

        return view('admin.employees.create', compact('employeeTypes', 'employmentStatuses', 'shifts', 'departments', 'designations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request)
    {
        DB::beginTransaction();

        try {
            $employee = $this->employeeService->create($request->validated(), $request->file('photo'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employees',
                'action' => 'create',
                'model' => 'Employee',
                'model_id' => $employee->id,
                'description' => 'Employee "' . $employee->full_name . '" created',
                'new_data' => $employee->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee created successfully.'
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
    public function edit(Employee $employee)
    {
        $employeeTypes = EmployeeType::active()->get();
        $employmentStatuses = EmploymentStatus::active()->get();
        $shifts = Shift::active()->get();
        $departments = Department::active()->get();
        $designations = Designation::active()->get();

        return view('admin.employees.edit', compact('employee', 'employeeTypes', 'employmentStatuses', 'shifts', 'departments', 'designations'));
    }

    public function findEmployee(string $id)
    {
        $employee = Employee::find($id);
        if(!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found'
            ]);
        }

        $employee->department_name = $employee->department ? $employee->department->name : 'Not Assigned';
        $employee->designation_name = $employee->designation ? $employee->designation->name : 'Not Assigned';

        return response()->json([
            'status' => true,
            'data' => $employee
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        DB::beginTransaction();

        try {
            $oldData = $employee->toArray();
            $updatedEmployee = $this->employeeService->update($employee, $request->validated(), $request->file('photo'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employees',
                'action' => 'update',
                'model' => 'Employee',
                'model_id' => $employee->id,
                'description' => 'Employee "' . $employee->full_name . '" updated',
                'new_data' => $updatedEmployee->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.employees.index'),
                'message' => 'Employee updated successfully.'
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
    public function destroy(Employee $employee)
    {
        DB::beginTransaction();

        try {
            $oldData = $employee->toArray();

            $this->employeeService->delete($employee);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employees',
                'action' => 'delete',
                'model' => 'Employee',
                'model_id' => $oldData['id'],
                'description' => 'Employee "' . $oldData['first_name'] . ' ' . $oldData['last_name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee deleted successfully.'
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
     * Display a full read-only profile of the employee (tabbed modal content).
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'employeeType', 'employmentStatus', 'shift', 'department', 'designation', 'admin',
            'documents' => fn($q) => $q->latest(),
            'emergencyContacts' => fn($q) => $q->latest(),
            'bankAccounts' => fn($q) => $q->latest(),
            'educations' => fn($q) => $q->latest(),
            'experiences' => fn($q) => $q->latest(),
            'transfers' => fn($q) => $q->latest(),
            'promotions' => fn($q) => $q->latest(),
            'resignations' => fn($q) => $q->latest(),
            'terminations' => fn($q) => $q->latest(),
            'attendances' => fn($q) => $q->latest('attendance_date')->limit(30),
            'leaveBalances' => fn($q) => $q->latest(),
            'leaveRequests' => fn($q) => $q->latest(),
            'salaryStructures' => fn($q) => $q->latest('effective_date'),
            'payrolls' => fn($q) => $q->latest(),
            'expenseClaims' => fn($q) => $q->latest(),
            'employeeLoans' => fn($q) => $q->latest(),
            'performanceReviews' => fn($q) => $q->latest('review_period_end'),
        ]);

        return view('admin.employees.show', compact('employee'));
    }

    /**
     * Show the form to create a login (Admin/Stuff account) for this employee.
     */
    public function createLogin(Employee $employee)
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return view('admin.employees.create-login', compact('employee', 'roles'));
    }

    /**
     * Create the Admin/Stuff login account linked to this employee.
     */
    public function storeLogin(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'email' => 'required|email|max:150|unique:admins,email',
            'password' => 'required|confirmed|min:8|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        if ($employee->admin) {
            return response()->json([
                'status' => false,
                'message' => 'This employee already has a login account.'
            ]);
        }

        DB::beginTransaction();

        try {
            $admin = new \App\Models\Admin();
            $admin->employee_id = $employee->id;
            $admin->name = $employee->full_name;
            $admin->username = $request->email;
            $admin->email = $request->email;
            $admin->password = Hash::make($request->password);
            $admin->status = true;
            $admin->save();

            $role = Role::findOrFail($request->role_id);
            $admin->assignRole($role);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employees',
                'action' => 'create-login',
                'model' => 'Employee',
                'model_id' => $employee->id,
                'description' => 'Login account created for employee "' . $employee->full_name . '"',
                'new_data' => ['admin_id' => $admin->id, 'email' => $admin->email, 'role' => $role->name],
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Login account created successfully.'
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
     * Export employees to CSV.
     */
    public function export()
    {
        $employees = Employee::with(['employeeType', 'employmentStatus', 'department', 'designation'])->get();

        $filename = 'employees_' . now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = [
            'Employee Code', 'First Name', 'Last Name', 'Email', 'Phone', 'Gender',
            'Date of Birth', 'Date of Joining', 'Department', 'Designation',
            'Employee Type', 'Employment Status', 'Status',
        ];

        $callback = function () use ($employees, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($employees as $employee) {
                fputcsv($handle, [
                    $employee->employee_code,
                    $employee->first_name,
                    $employee->last_name,
                    $employee->email,
                    $employee->phone,
                    $employee->gender,
                    $employee->date_of_birth?->format('Y-m-d'),
                    $employee->date_of_joining?->format('Y-m-d'),
                    $employee->department->name ?? '',
                    $employee->designation->name ?? '',
                    $employee->employeeType->name ?? '',
                    $employee->employmentStatus->name ?? '',
                    $employee->status ? 'Active' : 'Inactive',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the import form.
     */
    public function importForm()
    {
        return view('admin.employees.import');
    }

    /**
     * Import employees from a CSV file.
     *
     * Expected columns (header row required): Employee Code, First Name, Last Name, Email, Phone,
     * Gender, Date of Birth, Date of Joining, Department, Designation.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);

                if (empty($data['employee code']) || empty($data['email'])) {
                    $skipped++;
                    continue;
                }

                if (Employee::where('employee_code', $data['employee code'])->exists()
                    || Employee::where('email', $data['email'])->exists()) {
                    $skipped++;
                    continue;
                }

                $department = !empty($data['department']) ? Department::where('name', $data['department'])->first() : null;
                $designation = !empty($data['designation']) ? Designation::where('name', $data['designation'])->first() : null;

                Employee::create([
                    'employee_code' => $data['employee code'],
                    'first_name' => $data['first name'] ?? '',
                    'last_name' => $data['last name'] ?? '',
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'date_of_birth' => !empty($data['date of birth']) ? $data['date of birth'] : null,
                    'date_of_joining' => !empty($data['date of joining']) ? $data['date of joining'] : now()->toDateString(),
                    'department_id' => $department?->id,
                    'designation_id' => $designation?->id,
                    'status' => true,
                ]);

                $imported++;
            }

            fclose($handle);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employees',
                'action' => 'import',
                'model' => 'Employee',
                'description' => "Imported {$imported} employees, skipped {$skipped}",
                'new_data' => ['imported' => $imported, 'skipped' => $skipped],
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.employees.index'),
                'message' => "Imported {$imported} employees. Skipped {$skipped} (duplicate or invalid rows)."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Import failed: ' . $e->getMessage()
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

        $model = Employee::find($id);
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
