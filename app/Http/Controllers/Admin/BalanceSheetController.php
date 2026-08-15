<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BalanceSheetController extends Controller
{
    /**
     * Display the Balance Sheet — the financial position as of a given
     * date: total assets on one side, total liabilities plus equity on the
     * other, which must equal each other.
     *
     * Pure read-only report: no new table/Model/Service/Request, same
     * "controller only" shape as General Ledger/Cash Book/Accounts
     * Receivable/Accounts Payable/Trial Balance/Profit and Loss. Per this
     * project's established precedent (documented in Stock Valuation's,
     * Low Stock Alerts', General Ledger's, Trial Balance's and Profit and
     * Loss's own changelog entries), this controller computes its own
     * aggregation independently rather than sharing a service.
     *
     * Only `entry_status = 'posted'` journal entries are read — same
     * stricter-than-Inventory filter every prior Accounting report uses.
     *
     * This is the complement of Profit and Loss: P&L covers the two
     * nominal types (`revenue`/`expense`) over a period, while the Balance
     * Sheet covers the three real types (`asset`/`liability`/`equity`) as
     * of a single cut-off date — together they account for all five.
     *
     * Crucially, the two sides only reconcile once **retained earnings**
     * (cumulative revenue less cumulative expenses up to the same cut-off
     * date) is added into equity — nominal accounts are never closed out
     * into a real equity account by any module in this project, so that
     * closing entry is computed here at report time rather than being
     * expected to exist as posted data.
     */
    public function index(Request $request)
    {
        $asOfDate = $request->as_of_date;
        $hideZero = $request->hide_zero;

        $assetRows = $this->buildRows('asset', 'debit', $asOfDate);
        $liabilityRows = $this->buildRows('liability', 'credit', $asOfDate);
        $equityRows = $this->buildRows('equity', 'credit', $asOfDate);

        if ($hideZero) {
            $assetRows = $assetRows->filter(fn ($r) => abs($r['amount']) > 0.001)->values();
            $liabilityRows = $liabilityRows->filter(fn ($r) => abs($r['amount']) > 0.001)->values();
            $equityRows = $equityRows->filter(fn ($r) => abs($r['amount']) > 0.001)->values();
        }

        $retainedEarnings = $this->calculateRetainedEarnings($asOfDate);

        $totalAssets = round($assetRows->sum('amount'), 2);
        $totalLiabilities = round($liabilityRows->sum('amount'), 2);
        $totalEquityAccounts = round($equityRows->sum('amount'), 2);
        $totalEquity = round($totalEquityAccounts + $retainedEarnings, 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);

        $difference = round($totalAssets - $totalLiabilitiesAndEquity, 2);
        $isBalanced = abs($difference) < 0.005;

        return view('admin.balance-sheet.index', compact(
            'assetRows',
            'liabilityRows',
            'equityRows',
            'asOfDate',
            'hideZero',
            'retainedEarnings',
            'totalAssets',
            'totalLiabilities',
            'totalEquityAccounts',
            'totalEquity',
            'totalLiabilitiesAndEquity',
            'difference',
            'isBalanced'
        ));
    }

    /**
     * One row per postable account of the given type, with its cumulative
     * movement up to the cut-off date reduced to a single positive-is-
     * normal `amount`.
     *
     * Direction is fixed per account type (assets are held on the debit
     * side, liabilities and equity on the credit side) rather than read
     * from the account's own `normal_balance` accessor — the same
     * per-section fixed-direction approach Profit and Loss uses, so every
     * section reads as a positive magnitude. A negative figure (e.g. an
     * overdrawn bank account sitting on the credit side) is reported as-is
     * rather than clamped, since that is real information.
     */
    protected function buildRows(string $type, string $side, ?string $asOfDate): Collection
    {
        $accounts = ChartOfAccount::active()
            ->where('is_group', false)
            ->where('account_type', $type)
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) use ($side, $asOfDate) {
            $items = JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('entry_status', 'posted');
                    if ($asOfDate) {
                        $q->where('entry_date', '<=', $asOfDate);
                    }
                })
                ->get(['debit', 'credit']);

            $totalDebit = (float) $items->sum('debit');
            $totalCredit = (float) $items->sum('credit');

            $amount = $side === 'debit'
                ? round($totalDebit - $totalCredit, 2)
                : round($totalCredit - $totalDebit, 2);

            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'amount' => $amount,
            ];
        })->values();
    }

    /**
     * Cumulative net profit up to the cut-off date (all posted revenue
     * less all posted expenses), added into equity so the two sides of
     * the sheet reconcile. See the class docblock for why this is
     * computed here rather than read from a posted closing entry.
     */
    protected function calculateRetainedEarnings(?string $asOfDate): float
    {
        $totals = [];

        foreach (['revenue', 'expense'] as $type) {
            // `active()` matches buildRows() exactly, so an inactive account
            // can never be counted on one side of the sheet but not the other.
            $accountIds = ChartOfAccount::active()
                ->where('is_group', false)
                ->where('account_type', $type)
                ->pluck('id');

            $items = JournalEntryItem::whereIn('chart_of_account_id', $accountIds)
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('entry_status', 'posted');
                    if ($asOfDate) {
                        $q->where('entry_date', '<=', $asOfDate);
                    }
                })
                ->get(['debit', 'credit']);

            $totals[$type] = $type === 'revenue'
                ? (float) $items->sum('credit') - (float) $items->sum('debit')
                : (float) $items->sum('debit') - (float) $items->sum('credit');
        }

        return round($totals['revenue'] - $totals['expense'], 2);
    }
}
