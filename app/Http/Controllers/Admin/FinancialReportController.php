<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinancialReportController extends Controller
{
    /**
     * Display Financial Reports — the Accounting module's landing
     * dashboard: the headline figures from every statement built in this
     * module on one screen, a month-by-month revenue/expense trend, and a
     * directory linking through to each full statement.
     *
     * Pure read-only report: no new table/Model/Service/Request, same
     * "controller only" shape as every other Accounting report and the
     * same role `SalesReportController`/`PurchaseReportController`/
     * `InventoryReportController` play as the closing module of their own
     * sections. Per this project's established precedent (documented in
     * Stock Valuation's, Low Stock Alerts', General Ledger's, Trial
     * Balance's, Profit and Loss's, Balance Sheet's and Cash Flow's own
     * changelog entries), this controller computes its own aggregations
     * independently rather than calling into the individual statement
     * controllers — so a change to one statement can never silently
     * change what this dashboard reports, and vice versa.
     *
     * Only `entry_status = 'posted'` journal entries are counted, the
     * same stricter-than-Inventory filter every prior Accounting report
     * uses.
     */
    public function index(Request $request)
    {
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        // --- Profit and Loss headline (period) ---
        $totalRevenue = $this->nominalTotal('revenue', $dateFrom, $dateTo);
        $totalExpense = $this->nominalTotal('expense', $dateFrom, $dateTo);
        $netProfit = round($totalRevenue - $totalExpense, 2);
        $isProfit = $netProfit >= 0;
        $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : null;

        // --- Balance Sheet headline (as of the period end) ---
        $totalAssets = $this->realTotal('asset', 'debit', $dateTo);
        $totalLiabilities = $this->realTotal('liability', 'credit', $dateTo);
        $equityAccounts = $this->realTotal('equity', 'credit', $dateTo);
        // Cumulative retained earnings to date — nominal accounts are never
        // closed into equity anywhere in this project, so the same
        // report-time computation Balance Sheet documents is repeated here.
        $retainedEarnings = round(
            $this->nominalTotal('revenue', null, $dateTo) - $this->nominalTotal('expense', null, $dateTo),
            2
        );
        $totalEquity = round($equityAccounts + $retainedEarnings, 2);
        $balanceSheetBalanced = abs(round($totalAssets - ($totalLiabilities + $totalEquity), 2)) < 0.005;

        // --- Cash position (Cash Book's cash-account heuristic) ---
        $cashBalance = $this->cashBalance($dateTo);

        // --- Receivables / Payables outstanding ---
        $receivable = $this->outstanding(SalesInvoice::class, 'invoice_status');
        $payable = $this->outstanding(PurchaseInvoice::class, 'invoice_status');

        // --- Ledger health ---
        $postedEntries = JournalEntry::where('entry_status', 'posted')
            ->when($dateFrom, fn ($q) => $q->where('entry_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('entry_date', '<=', $dateTo))
            ->count();
        $draftEntries = JournalEntry::where('entry_status', 'draft')->count();
        $activeTaxRates = TaxRate::active()->count();

        $monthlyTrend = $this->monthlyTrend($dateFrom, $dateTo);

        return view('admin.financial-reports.index', compact(
            'dateFrom',
            'dateTo',
            'totalRevenue',
            'totalExpense',
            'netProfit',
            'isProfit',
            'profitMargin',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'retainedEarnings',
            'balanceSheetBalanced',
            'cashBalance',
            'receivable',
            'payable',
            'postedEntries',
            'draftEntries',
            'activeTaxRates',
            'monthlyTrend'
        ));
    }

    /**
     * Period movement on a nominal (revenue/expense) account type,
     * reduced to a positive-is-normal figure — the same per-type fixed
     * direction Profit and Loss uses.
     */
    protected function nominalTotal(string $type, ?string $dateFrom, ?string $dateTo): float
    {
        $items = $this->itemsForType($type, $dateFrom, $dateTo);

        $debit = (float) $items->sum('debit');
        $credit = (float) $items->sum('credit');

        return $type === 'revenue' ? round($credit - $debit, 2) : round($debit - $credit, 2);
    }

    /**
     * Cumulative balance on a real (asset/liability/equity) account type
     * as of the cut-off date — the same per-type fixed direction the
     * Balance Sheet uses.
     */
    protected function realTotal(string $type, string $side, ?string $dateTo): float
    {
        $items = $this->itemsForType($type, null, $dateTo);

        $debit = (float) $items->sum('debit');
        $credit = (float) $items->sum('credit');

        return $side === 'debit' ? round($debit - $credit, 2) : round($credit - $debit, 2);
    }

    /**
     * All posted journal lines hitting any active, postable account of the
     * given type within the optional date bounds.
     */
    protected function itemsForType(string $type, ?string $dateFrom, ?string $dateTo): Collection
    {
        $accountIds = ChartOfAccount::active()
            ->where('is_group', false)
            ->where('account_type', $type)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        return JournalEntryItem::whereIn('chart_of_account_id', $accountIds)
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('entry_status', 'posted');
                if ($dateFrom) {
                    $q->where('entry_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->where('entry_date', '<=', $dateTo);
                }
            })
            ->get(['debit', 'credit']);
    }

    /**
     * Cash and bank balance as of the cut-off date, using the same
     * cash-account heuristic Cash Book and Cash Flow established (a
     * postable, asset-natured account whose Account Type is named
     * something containing "cash" or "bank"). Returns 0 when no such
     * account has been configured, which is the honest answer rather
     * than a guess.
     */
    protected function cashBalance(?string $dateTo): float
    {
        $cashAccountIds = ChartOfAccount::active()
            ->where('is_group', false)
            ->where('account_type', 'asset')
            ->whereHas('accountType', function ($q) {
                $q->where('name', 'like', '%cash%')->orWhere('name', 'like', '%bank%');
            })
            ->pluck('id');

        if ($cashAccountIds->isEmpty()) {
            return 0.0;
        }

        $items = JournalEntryItem::whereIn('chart_of_account_id', $cashAccountIds)
            ->whereHas('journalEntry', function ($q) use ($dateTo) {
                $q->where('entry_status', 'posted');
                if ($dateTo) {
                    $q->where('entry_date', '<=', $dateTo);
                }
            })
            ->get(['debit', 'credit']);

        return round((float) $items->sum('debit') - (float) $items->sum('credit'), 2);
    }

    /**
     * Outstanding balance across all non-cancelled invoices of the given
     * model. `balance_due` is an accessor rather than a column on both
     * invoice models, so the sum is computed in PHP — the same constraint
     * Accounts Receivable/Payable and Sales/Purchase Reports already work
     * around the same way.
     */
    protected function outstanding(string $modelClass, string $statusColumn): array
    {
        $invoices = $modelClass::where($statusColumn, '!=', 'cancelled')->get();

        $total = round($invoices->sum(fn ($invoice) => max(0, (float) $invoice->balance_due)), 2);

        $overdue = $invoices->filter(function ($invoice) {
            return (float) $invoice->balance_due > 0
                && $invoice->due_date
                && $invoice->due_date->isPast();
        });

        return [
            'total' => $total,
            'invoice_count' => $invoices->count(),
            'overdue_count' => $overdue->count(),
            'overdue_total' => round($overdue->sum(fn ($invoice) => (float) $invoice->balance_due), 2),
        ];
    }

    /**
     * Revenue, expense and net profit per calendar month across the
     * selected range — the one figure on this page that no individual
     * statement provides, since each of them reports a single period or
     * point in time rather than a trend. Falls back to the last 6 months
     * when no range is given, so the chart is never empty on first load.
     */
    protected function monthlyTrend(?string $dateFrom, ?string $dateTo): Collection
    {
        $end = $dateTo ? Carbon::parse($dateTo)->endOfMonth() : Carbon::now()->endOfMonth();
        $start = $dateFrom ? Carbon::parse($dateFrom)->startOfMonth() : (clone $end)->subMonths(5)->startOfMonth();

        // Guard against an inverted or absurdly wide range producing a
        // runaway loop — 24 months is more than any dashboard chart needs.
        if ($start->gt($end)) {
            $start = (clone $end)->startOfMonth();
        }

        $months = collect();
        $cursor = (clone $start);
        $guard = 0;

        while ($cursor->lte($end) && $guard < 24) {
            $monthStart = (clone $cursor)->startOfMonth()->toDateString();
            $monthEnd = (clone $cursor)->endOfMonth()->toDateString();

            $revenue = $this->nominalTotal('revenue', $monthStart, $monthEnd);
            $expense = $this->nominalTotal('expense', $monthStart, $monthEnd);

            $months->push([
                'label' => $cursor->format('M Y'),
                'revenue' => $revenue,
                'expense' => $expense,
                'net' => round($revenue - $expense, 2),
            ]);

            $cursor->addMonth();
            $guard++;
        }

        return $months;
    }
}
