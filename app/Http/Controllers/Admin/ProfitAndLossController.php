<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProfitAndLossController extends Controller
{
    /**
     * Display the Profit and Loss statement — total revenue earned less
     * total expenses incurred over a date range, giving net profit or loss.
     *
     * Pure read-only report: no new table/Model/Service/Request, same
     * "controller only" shape as General Ledger/Cash Book/Accounts
     * Receivable/Accounts Payable/Trial Balance. Per this project's
     * established precedent (documented in Stock Valuation's, Low Stock
     * Alerts', General Ledger's and Trial Balance's own changelog entries),
     * this controller computes its own aggregation independently rather
     * than sharing a service with the other Accounting reports.
     *
     * Only `entry_status = 'posted'` journal entries are read — same
     * stricter-than-Inventory filter every prior Accounting report uses
     * (a draft entry isn't yet a financial fact; a cancelled one never
     * happened).
     *
     * Unlike Trial Balance (a point-in-time snapshot of every account),
     * a P&L covers a *period* and only reads the two nominal account
     * types — `revenue` and `expense`. Asset/liability/equity accounts are
     * balance-sheet accounts and are deliberately excluded here; they
     * belong to the Balance Sheet report (the next roadmap item).
     */
    public function index(Request $request)
    {
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $hideZero = $request->hide_zero;

        $revenueAccounts = ChartOfAccount::active()
            ->where('is_group', false)
            ->where('account_type', 'revenue')
            ->orderBy('code')
            ->get();

        $expenseAccounts = ChartOfAccount::active()
            ->where('is_group', false)
            ->where('account_type', 'expense')
            ->orderBy('code')
            ->get();

        $revenueRows = $this->buildRows($revenueAccounts, 'revenue', $dateFrom, $dateTo);
        $expenseRows = $this->buildRows($expenseAccounts, 'expense', $dateFrom, $dateTo);

        if ($hideZero) {
            $revenueRows = $revenueRows->filter(fn ($row) => abs($row['amount']) > 0.001)->values();
            $expenseRows = $expenseRows->filter(fn ($row) => abs($row['amount']) > 0.001)->values();
        }

        $totalRevenue = round($revenueRows->sum('amount'), 2);
        $totalExpense = round($expenseRows->sum('amount'), 2);
        $netProfit = round($totalRevenue - $totalExpense, 2);
        $isProfit = $netProfit >= 0;

        // Margin is only meaningful when there is revenue to measure against.
        $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : null;

        return view('admin.profit-and-loss.index', compact(
            'revenueRows',
            'expenseRows',
            'dateFrom',
            'dateTo',
            'hideZero',
            'totalRevenue',
            'totalExpense',
            'netProfit',
            'isProfit',
            'profitMargin'
        ));
    }

    /**
     * One row per nominal account, with its period movement reduced to a
     * single positive-is-normal `amount`.
     *
     * Direction is fixed per account type rather than read from the
     * account's own `normal_balance` accessor: a revenue account earns on
     * the credit side (`credit - debit`) and an expense account is
     * incurred on the debit side (`debit - credit`). A refund or reversal
     * can legitimately push either figure negative, which is reported
     * as-is rather than clamped to zero — a negative revenue line is real
     * information (net of refunds), not an error to hide.
     */
    protected function buildRows(Collection $accounts, string $type, ?string $dateFrom, ?string $dateTo): Collection
    {
        return $accounts->map(function ($account) use ($type, $dateFrom, $dateTo) {
            $items = JournalEntryItem::where('chart_of_account_id', $account->id)
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

            $totalDebit = (float) $items->sum('debit');
            $totalCredit = (float) $items->sum('credit');

            $amount = $type === 'revenue'
                ? round($totalCredit - $totalDebit, 2)
                : round($totalDebit - $totalCredit, 2);

            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'amount' => $amount,
            ];
        })->values();
    }
}
