<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EmployeeAssetTrackingController extends Controller
{
    /**
     * Employee Asset Tracking — who is holding what, per employee.
     *
     * Pure read-only report: no new table/Model/Service/Request. Asset
     * Assignment already records every handover and return against
     * `employees.id`, so this module adds no new data entry — it reads that
     * history from the employee's side instead of the asset's, the same
     * "controller only" shape General Ledger/Cash Book/Accounts
     * Receivable/Accounts Payable use. Per this project's established
     * precedent, it computes its own aggregation independently rather than
     * sharing a service with `AssetAssignmentController`.
     *
     * Two modes on one page, the same shape General Ledger established:
     * with no employee selected it shows a per-employee summary (currently
     * held, overdue, lifetime assignments) across everyone with any
     * assignment history; selecting one drills into their full holdings and
     * past returns.
     */
    public function index(Request $request)
    {
        $employeeId = $request->employee_id;
        $onlyCurrent = $request->only_current;

        $employees = Employee::active()->orderBy('first_name')->get();

        $selectedEmployee = null;
        $holdings = null;
        $summary = null;

        if ($employeeId) {
            $selectedEmployee = $employees->firstWhere('id', (int) $employeeId) ?? Employee::find($employeeId);

            if ($selectedEmployee) {
                $holdings = $this->buildEmployeeHoldings($selectedEmployee, $onlyCurrent);
            }
        } else {
            $summary = $this->buildEmployeesSummary($onlyCurrent);
        }

        $totals = $this->buildTotals();

        return view('admin.employee-asset-tracking.index', compact(
            'employees',
            'employeeId',
            'onlyCurrent',
            'selectedEmployee',
            'holdings',
            'summary',
            'totals'
        ));
    }

    /**
     * One employee's assignment records — currently held first, then past
     * returns, each with its overdue state.
     */
    protected function buildEmployeeHoldings(Employee $employee, $onlyCurrent): array
    {
        $query = AssetAssignment::with('asset.assetCategory')
            ->where('employee_id', $employee->id);

        if ($onlyCurrent) {
            $query->where('assignment_status', 'assigned');
        }

        $assignments = $query->orderByRaw("FIELD(assignment_status, 'assigned', 'returned', 'cancelled')")
            ->orderBy('assigned_date', 'DESC')
            ->get();

        return [
            'assignments' => $assignments,
            'currently_held' => $assignments->where('assignment_status', 'assigned')->count(),
            'overdue' => $assignments->filter(fn ($a) => $a->is_overdue)->count(),
            'returned' => $assignments->where('assignment_status', 'returned')->count(),
            'total' => $assignments->count(),
        ];
    }

    /**
     * Per-employee totals across everyone who has ever held an asset.
     * Employees with no assignment history at all are excluded — listing
     * every employee on the payroll would bury the ones this report is
     * actually about (the same "filter to rows with activity" refinement
     * Accounts Receivable's own summary uses).
     */
    protected function buildEmployeesSummary($onlyCurrent): Collection
    {
        $assignments = AssetAssignment::with('employee')->get();

        return $assignments
            ->filter(fn ($a) => $a->employee !== null)
            ->groupBy('employee_id')
            ->map(function ($rows) {
                $employee = $rows->first()->employee;

                return [
                    'employee_id' => $employee->id,
                    'name' => trim($employee->first_name . ' ' . $employee->last_name),
                    'employee_code' => $employee->employee_code,
                    'currently_held' => $rows->where('assignment_status', 'assigned')->count(),
                    'overdue' => $rows->filter(fn ($a) => $a->is_overdue)->count(),
                    'returned' => $rows->where('assignment_status', 'returned')->count(),
                    'total' => $rows->count(),
                ];
            })
            ->when($onlyCurrent, fn ($rows) => $rows->filter(fn ($row) => $row['currently_held'] > 0))
            ->sortByDesc('currently_held')
            ->values();
    }

    /**
     * Headline counts across the whole organisation.
     */
    protected function buildTotals(): array
    {
        $assigned = AssetAssignment::where('assignment_status', 'assigned')->with('employee')->get();

        return [
            'assets_out' => $assigned->count(),
            'employees_holding' => $assigned->pluck('employee_id')->unique()->count(),
            'overdue' => $assigned->filter(fn ($a) => $a->is_overdue)->count(),
            'lifetime_assignments' => AssetAssignment::count(),
        ];
    }
}
