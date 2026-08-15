<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\ProjectInvoice;
use App\Models\ProjectTimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProjectReportController extends Controller
{
    /**
     * Project Profitability Reports — the module's fourteenth and final
     * checklist item: revenue billed, cost incurred, budget vs. actual, and
     * a resulting profit/margin per project.
     *
     * Pure read-only report: no new table/Model/Service/Request, the same
     * closing-module role Financial Reports, Sales Reports, Purchase
     * Reports, Inventory Reports and Asset Reports play for their own
     * sections — this computes its own aggregation independently rather
     * than sharing a service with the modules it reads from.
     *
     * Scope decisions, checked against the actual code before writing any
     * of this (not assumed):
     *
     * - Revenue is every non-cancelled Project Invoice's `grand_total` for
     *   the project — the same "exclude cancelled" convention every prior
     *   Sales/Purchase/Accounting report in this project already follows.
     *   `balance_due` is an accessor, not a column, so outstanding amounts
     *   are summed in PHP, the same constraint Accounts Receivable/Payable
     *   and Sales/Purchase Reports already work around identically.
     *
     * - Cost is every `approval_status = 'approved'` Project Expense's
     *   `amount` — regardless of `is_billable`. A cost is real to the
     *   project whether or not the client is being charged for it;
     *   `is_billable` only governs whether it can be billed on a Project
     *   Invoice (`ProjectExpense::scopeBillableApproved()`, reused here for
     *   the separate "recoverable cost" figure), not whether it happened.
     *
     * - Time Tracking is deliberately NOT folded into the cost figure.
     *   `ProjectTimeEntry` was inspected directly (its migration, model and
     *   every changelog entry) and carries no hourly rate anywhere — not on
     *   the entry, not on `Employee`, not on any Payroll/Salary Structure
     *   table checked in HRM. Inventing a rate to price labour hours would
     *   misrepresent cost with a fabricated number; the report instead
     *   shows hours logged (total and billable) as its own informational
     *   figure, explicitly labelled as excluded from the profit/loss math,
     *   with a callout stating why. If a per-employee or per-project billing
     *   rate is ever added to Time Tracking, folding labour cost in here
     *   becomes a small, natural follow-up — not invented speculatively now.
     *
     * - "The budget" for a project is its highest-`version` active
     *   `ProjectBudget` row, regardless of `budget_status` — Project Budgets
     *   is explicitly versioned (draft → approved → revised → closed) per
     *   project, and the most recently numbered version is the one that
     *   currently represents "what was planned", the same way an edited
     *   document's latest state is what matters, not its approval history.
     *   A project with no budget at all is reported as unbudgeted rather
     *   than assumed to be zero.
     *
     * - Budget-vs-actual by category compares `project_budget_items` against
     *   approved `project_expenses` grouped by `expense_category` — the two
     *   tables deliberately share the same 7-value category enum (documented
     *   in Project Expenses' own changelog entry specifically so this
     *   comparison would be meaningful), so no category-name mapping was
     *   needed.
     */
    public function index(Request $request)
    {
        $projectId = $request->project_id;

        $projects = Project::active()->orderBy('name')->get();

        if ($projectId) {
            $project = Project::with('client')->findOrFail($projectId);

            return view('admin.project-reports.index', [
                'projects' => $projects,
                'projectId' => $projectId,
                'mode' => 'detail',
                'project' => $project,
                'row' => $this->buildProjectRow($project),
                'budget' => $this->currentBudget($project->id),
                'categoryBreakdown' => $this->categoryBreakdown($project->id),
            ]);
        }

        $rows = $projects->map(fn ($project) => $this->buildProjectRow($project));

        $totals = [
            'revenue' => round($rows->sum('revenue'), 2),
            'cost' => round($rows->sum('cost'), 2),
            'profit' => round($rows->sum('profit'), 2),
            'outstanding' => round($rows->sum('outstanding'), 2),
            'budgeted_projects' => $rows->filter(fn ($r) => $r['budget_total'] !== null)->count(),
            'over_budget_projects' => $rows->filter(fn ($r) => $r['budget_total'] !== null && $r['cost'] > $r['budget_total'])->count(),
            'total_hours' => round($rows->sum('total_hours'), 2),
            'billable_hours' => round($rows->sum('billable_hours'), 2),
        ];
        $totals['margin'] = $totals['revenue'] > 0
            ? round(($totals['profit'] / $totals['revenue']) * 100, 2)
            : null;

        return view('admin.project-reports.index', [
            'projects' => $projects,
            'projectId' => $projectId,
            'mode' => 'overview',
            'rows' => $rows->sortByDesc('profit')->values(),
            'totals' => $totals,
        ]);
    }

    /**
     * Revenue, cost, profit/margin, budget position and hours for a single
     * project. Used both per-row in the overview and for the detail view's
     * headline figures, so the two can never disagree about how a project's
     * own numbers are computed.
     */
    protected function buildProjectRow(Project $project): array
    {
        $revenue = round(
            (float) ProjectInvoice::where('project_id', $project->id)
                ->where('invoice_status', '!=', 'cancelled')
                ->sum('grand_total'),
            2
        );

        $outstanding = round(
            ProjectInvoice::where('project_id', $project->id)
                ->where('invoice_status', '!=', 'cancelled')
                ->get()
                ->sum(fn ($invoice) => max(0, (float) $invoice->balance_due)),
            2
        );

        $cost = round(
            (float) ProjectExpense::where('project_id', $project->id)
                ->where('approval_status', 'approved')
                ->sum('amount'),
            2
        );

        $billableCost = round(
            (float) ProjectExpense::where('project_id', $project->id)
                ->billableApproved()
                ->sum('amount'),
            2
        );

        $profit = round($revenue - $cost, 2);
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : null;

        $budget = $this->currentBudget($project->id);
        $budgetTotal = $budget ? round((float) $budget->total_amount, 2) : null;
        $budgetVariance = $budgetTotal !== null ? round($budgetTotal - $cost, 2) : null;
        $budgetUsedPercent = $budgetTotal !== null && $budgetTotal > 0
            ? round(($cost / $budgetTotal) * 100, 2)
            : null;

        $hours = ProjectTimeEntry::where('project_id', $project->id)
            ->whereNotNull('hours')
            ->selectRaw('sum(hours) as total_hours, sum(case when is_billable = 1 then hours else 0 end) as billable_hours')
            ->first();

        return [
            'project' => $project,
            'revenue' => $revenue,
            'outstanding' => $outstanding,
            'cost' => $cost,
            'billable_cost' => $billableCost,
            'profit' => $profit,
            'margin' => $margin,
            'budget_total' => $budgetTotal,
            'budget_version' => $budget?->version_label,
            'budget_variance' => $budgetVariance,
            'budget_used_percent' => $budgetUsedPercent,
            'total_hours' => round((float) ($hours->total_hours ?? 0), 2),
            'billable_hours' => round((float) ($hours->billable_hours ?? 0), 2),
        ];
    }

    /**
     * The project's own most-current budget — its highest active `version`,
     * regardless of status. Null when the project has never had one.
     */
    protected function currentBudget(int $projectId): ?ProjectBudget
    {
        return ProjectBudget::with('items')
            ->where('project_id', $projectId)
            ->where('status', true)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Budgeted vs. actual (approved) spend per category, for the detail
     * view's line-by-line breakdown. Every category present in either side
     * is shown, even a budget line with no matching spend yet or an expense
     * category the budget never planned for.
     */
    protected function categoryBreakdown(int $projectId): Collection
    {
        $budget = $this->currentBudget($projectId);

        $budgeted = $budget
            ? $budget->items->groupBy('category')->map(fn ($items) => (float) $items->sum('amount'))
            : collect();

        $actual = ProjectExpense::where('project_id', $projectId)
            ->where('approval_status', 'approved')
            ->selectRaw('expense_category, sum(amount) as total')
            ->groupBy('expense_category')
            ->pluck('total', 'expense_category')
            ->map(fn ($total) => (float) $total);

        $categories = $budgeted->keys()->merge($actual->keys())->unique()->sort()->values();

        return $categories->map(function ($category) use ($budgeted, $actual) {
            $budgetedAmount = round((float) ($budgeted[$category] ?? 0), 2);
            $actualAmount = round((float) ($actual[$category] ?? 0), 2);

            return [
                'category' => $category,
                'label' => ucfirst($category),
                'budgeted' => $budgetedAmount,
                'actual' => $actualAmount,
                'variance' => round($budgetedAmount - $actualAmount, 2),
            ];
        });
    }
}
