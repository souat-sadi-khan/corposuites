<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MinimumWageRule;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;

class PayrollComplianceReportController extends Controller
{
    /**
     * A per-employee minimum-wage compliance audit — the gap flagged
     * explicitly when Module 7 shipped: SalaryStructureRequest/
     * SalaryTemplateService only check compliance at the moment a
     * structure is created or bulk-assigned, so nothing previously
     * surfaced an employee who became non-compliant afterward (a new,
     * stricter rule added later; a corrected employee location; etc.).
     * This report re-checks every active employee's current structure
     * against today's rules, on demand, rather than storing a flag that
     * could itself go stale.
     */
    public function index(Request $request)
    {
        $employees = Employee::active()
            ->with(['department', 'designation'])
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->orderBy('first_name')
            ->get();

        // Same "active, latest effective_date" resolution PayrollService
        // and every other Salary Structure lookup in this project already
        // uses — this report can never disagree with Payroll generation
        // about which structure is "the current one".
        $activeStructures = SalaryStructure::active()
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($structures) => $structures->first());

        $rows = $employees->map(fn ($employee) => $this->buildRow($employee, $activeStructures->get($employee->id)));

        if ($request->pay_type) {
            $rows = $rows->filter(fn ($row) => $row['pay_type'] === $request->pay_type);
        }

        if ($request->compliance) {
            $rows = $rows->filter(fn ($row) => $row['status'] === $request->compliance);
        }

        $rows = $rows->values();

        $summary = [
            'total' => $rows->count(),
            'compliant' => $rows->where('status', 'compliant')->count(),
            'non_compliant' => $rows->where('status', 'non_compliant')->count(),
            'no_rule' => $rows->where('status', 'no_rule')->count(),
            'exempt' => $rows->where('status', 'exempt')->count(),
            'no_structure' => $rows->where('status', 'no_structure')->count(),
        ];

        $departments = Department::active()->orderBy('name')->get();
        $departmentId = $request->department_id;
        $payType = $request->pay_type;
        $compliance = $request->compliance;

        return view('admin.payroll-compliance-report.index', compact(
            'rows', 'summary', 'departments', 'departmentId', 'payType', 'compliance'
        ));
    }

    /**
     * Resolve one employee's compliance state as of right now. Returns a
     * uniform shape regardless of which branch applies, so the view never
     * has to guard against a missing key.
     */
    protected function buildRow(Employee $employee, ?SalaryStructure $structure): array
    {
        if (! $structure) {
            return [
                'employee' => $employee,
                'structure' => null,
                'pay_type' => null,
                'rate' => null,
                'rule' => null,
                'shortfall' => null,
                'status' => 'no_structure',
            ];
        }

        $payType = $structure->pay_type;
        $rate = (float) $structure->basic_salary;

        // Commission-based pay has no fixed rate to hold against a floor —
        // the same exemption MinimumWageComplianceService already applies.
        if (! in_array($payType, MinimumWageRule::WAGE_TYPES, true)) {
            return [
                'employee' => $employee,
                'structure' => $structure,
                'pay_type' => $payType,
                'rate' => $rate,
                'rule' => null,
                'shortfall' => null,
                'status' => 'exempt',
            ];
        }

        $rule = MinimumWageRule::resolveApplicable($employee->country, $employee->state, $payType);

        if (! $rule) {
            return [
                'employee' => $employee,
                'structure' => $structure,
                'pay_type' => $payType,
                'rate' => $rate,
                'rule' => null,
                'shortfall' => null,
                'status' => 'no_rule',
            ];
        }

        $compliant = $rate >= (float) $rule->minimum_wage;

        return [
            'employee' => $employee,
            'structure' => $structure,
            'pay_type' => $payType,
            'rate' => $rate,
            'rule' => $rule,
            'shortfall' => $compliant ? null : round((float) $rule->minimum_wage - $rate, 2),
            'status' => $compliant ? 'compliant' : 'non_compliant',
        ];
    }
}
