<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Http\Request;

class HrReportController extends Controller
{
    /**
     * Display the HR reporting dashboard.
     */
    public function index(Request $request)
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::active()->count();

        $employeesByDepartment = Employee::active()
            ->with('department')
            ->get()
            ->groupBy(fn($employee) => $employee->department->name ?? 'Unassigned')
            ->map->count()
            ->sortDesc()
            ->map(fn($total, $department) => (object) ['department' => $department, 'total' => $total])
            ->values();

        $employeesByType = Employee::active()
            ->with('employeeType')
            ->get()
            ->groupBy(fn($employee) => $employee->employeeType->name ?? 'Unassigned')
            ->map->count();

        $today = now()->toDateString();
        $attendanceToday = Attendance::where('attendance_date', $today)
            ->selectRaw('attendance_status, COUNT(*) as total')
            ->groupBy('attendance_status')
            ->pluck('total', 'attendance_status');

        $pendingLeaveRequests = LeaveRequest::where('approval_status', 'pending')->count();
        $approvedLeaveRequestsThisMonth = LeaveRequest::where('approval_status', 'approved')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();

        $pendingExpenseClaims = ExpenseClaim::where('approval_status', 'pending')->count();
        $approvedExpenseAmountThisMonth = ExpenseClaim::where('approval_status', 'approved')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $outstandingLoans = EmployeeLoan::where('approval_status', 'approved')
            ->get()
            ->sum('remaining_balance');

        $payrollThisMonth = Payroll::where('month', now()->month)
            ->where('year', now()->year)
            ->selectRaw('payment_status, COUNT(*) as total, SUM(net_salary) as amount')
            ->groupBy('payment_status')
            ->get();

        return view('admin.hr-reports.index', compact(
            'totalEmployees',
            'activeEmployees',
            'employeesByDepartment',
            'employeesByType',
            'attendanceToday',
            'pendingLeaveRequests',
            'approvedLeaveRequestsThisMonth',
            'pendingExpenseClaims',
            'approvedExpenseAmountThisMonth',
            'outstandingLoans',
            'payrollThisMonth'
        ));
    }
}
