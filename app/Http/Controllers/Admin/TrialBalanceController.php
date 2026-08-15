<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TrialBalanceController extends Controller
{
    /**
     * Display the Trial Balance — a single all-accounts snapshot listing
     * every postable (non-group) Chart of Accounts entry's closing balance
     * as of a given date, split into a Debit or Credit column depending on
     * which side the balance falls on.
     *
     * Pure read-only report: no new table/Model/Service/Request, same
     * "controller only" shape as General Ledger/Cash Book/Accounts
     * Receivable/Accounts Payable. Per this project's established
     * precedent (documented in Stock Valuation's, Low Stock Alerts' and
     * General Ledger's own changelog entries), this controller computes
     * its own aggregation independently rather than sharing a service
     * with General Ledger, so editing one report can never accidentally
     * change the other's behaviour.
     *
     * Only `entry_status = 'posted'` journal entries are read — same
     * stricter-than-Inventory filter General Ledger established (a draft
     * entry isn't yet a financial fact; a cancelled one never happened).
     *
     * The report's own built-in correctness check is that total debit must
     * equal total credit — a direct consequence of Journal Entries' own
     * balanced-entry validation. Any non-zero difference surfaces as a red
     * "Out of balance" badge rather than being silently hidden.
     */
    public function index(Request $request)
    {
        $asOfDate = $request->as_of_date;
        $accountType = $request->account_type;
        $hideZero = $request->hide_zero;

        $accountsQuery = ChartOfAccount::active()->where('is_group', false);

        if ($accountType) {
            $accountsQuery->where('account_type', $accountType);
        }

        $accounts = $accountsQuery->orderBy('code')->get();

        $rows = $this->buildTrialBalance($accounts, $asOfDate);

        if ($hideZero) {
            $rows = $rows->filter(fn ($row) => abs($row['debit']) > 0.001 || abs($row['credit']) > 0.001)->values();
        }

        $totalDebit = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');
        $difference = round($totalDebit - $totalCredit, 2);
        $isBalanced = abs($difference) < 0.005;

        return view('admin.trial-balance.index', compact(
            'rows',
            'asOfDate',
            'accountType',
            'hideZero',
            'totalDebit',
            'totalCredit',
            'difference',
            'isBalanced'
        ));
    }

    /**
     * One row per postable account: its total debit/credit movement and the
     * resulting closing balance, placed in the Debit or Credit column
     * according to which side the net balance falls on.
     *
     * The net is computed as raw `debit - credit` (not the normal-balance-
     * aware signed sum General Ledger uses for its per-account running
     * balance) — a trial balance needs the natural ledger side of each
     * account, so a positive net belongs in the Debit column and a negative
     * net in the Credit column, regardless of what the account's expected
     * normal side is. `normal_balance` is still carried through for display,
     * so an account sitting on the opposite side of its expected normal
     * balance is visible to the reader.
     */
    protected function buildTrialBalance(Collection $accounts, ?string $asOfDate): Collection
    {
        return $accounts->map(function ($account) use ($asOfDate) {
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
            $net = round($totalDebit - $totalCredit, 2);

            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type,
                'normal_balance' => $account->normal_balance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'debit' => $net > 0 ? $net : 0,
                'credit' => $net < 0 ? abs($net) : 0,
            ];
        })->values();
    }
}
