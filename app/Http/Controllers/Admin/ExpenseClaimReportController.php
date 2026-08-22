<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use Illuminate\Http\Request;

class ExpenseClaimReportController extends Controller
{
    /**
     * An enterprise-style spend report over Expense Claims — filterable by
     * category, department, employee, approval state, reimbursement state
     * and a date range, with a category breakdown table. This is the
     * concrete "also the report" half of the dynamic-category request:
     * everything here reads the same ExpenseCategory master the claim
     * form/list now uses, so a report row can never disagree with what a
     * claim was actually filed against.
     */
    public function index(Request $request)
    {
        $query = ExpenseClaim::query()->with(['employee.department', 'expenseCategory']);

        if ($request->expense_category_id) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        if ($request->department_id) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->approval_status) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->date_from) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $claims = $query->orderByDesc('expense_date')->get();

        $summary = [
            'total_claims' => $claims->count(),
            'total_amount' => $claims->sum('amount'),
            'pending' => $claims->where('approval_status', 'pending')->count(),
            'approved' => $claims->where('approval_status', 'approved')->count(),
            'rejected' => $claims->where('approval_status', 'rejected')->count(),
            'unreimbursed_amount' => $claims->where('approval_status', 'approved')->where('payment_status', 'unpaid')->sum('amount'),
        ];

        $byCategory = $claims
            ->groupBy(fn ($claim) => $claim->expenseCategory->name ?? ($claim->category_legacy ?: 'Uncategorised'))
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                    'approved_total' => $group->where('approval_status', 'approved')->sum('amount'),
                ];
            })
            ->sortByDesc('total');

        $byDepartment = $claims
            ->groupBy(fn ($claim) => $claim->employee?->department?->name ?? 'Unassigned')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            })
            ->sortByDesc('total');

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $departments = Department::active()->orderBy('name')->get();
        $employees = Employee::active()->orderBy('first_name')->get();

        return view('admin.expense-claim-reports.index', compact(
            'claims', 'summary', 'byCategory', 'byDepartment', 'categories', 'departments', 'employees'
        ));
    }
}
