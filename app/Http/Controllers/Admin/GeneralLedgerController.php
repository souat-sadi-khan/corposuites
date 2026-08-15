<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GeneralLedgerController extends Controller
{
    /**
     * Display the General Ledger — a per-account running-balance view built
     * entirely from posted Journal Entries. No new input table: this is a
     * pure read-only report, same "controller only, no new
     * table/Model/Service" shape as Inventory Transactions/Stock Valuation.
     *
     * Only `entry_status = 'posted'` journal entries are read here — a
     * draft entry hasn't been finalized yet and a cancelled one never
     * happened, so neither should affect a real ledger balance (a stricter
     * filter than Inventory's own reports, which merely exclude
     * `cancelled` and still count `draft`/`pending`-equivalent documents,
     * since a stock movement document's "draft" state doesn't have the
     * same "not yet a real financial fact" weight a journal entry's does).
     *
     * With no account selected, shows a summary (ending balance per
     * account, computed as of `date_to` if given) across every postable
     * account with any posted activity — deliberately kept minimal so it
     * doesn't preempt the future Trial Balance report's job. Selecting one
     * account drills into its full running-balance transaction detail.
     */
    public function index(Request $request)
    {
        $accountId = $request->account_id;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $accounts = ChartOfAccount::active()->where('is_group', false)->orderBy('code')->get();

        $selectedAccount = null;
        $ledger = null;
        $summary = null;

        if ($accountId) {
            $selectedAccount = $accounts->firstWhere('id', (int) $accountId) ?? ChartOfAccount::find($accountId);

            if ($selectedAccount) {
                $ledger = $this->buildAccountLedger($selectedAccount, $dateFrom, $dateTo);
            }
        } else {
            $summary = $this->buildAccountsSummary($accounts, $dateTo);
        }

        return view('admin.general-ledger.index', compact(
            'accounts',
            'accountId',
            'dateFrom',
            'dateTo',
            'selectedAccount',
            'ledger',
            'summary'
        ));
    }

    /**
     * Build the full running-balance transaction detail for one account:
     * an opening balance (brought forward from everything posted before
     * `dateFrom`, if given), each posted line within the range with a
     * running balance, and closing totals.
     */
    protected function buildAccountLedger(ChartOfAccount $account, ?string $dateFrom, ?string $dateTo): array
    {
        $normalBalance = $account->normal_balance;

        $openingBalance = 0;

        if ($dateFrom) {
            $priorItems = JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom) {
                    $q->where('entry_status', 'posted')->where('entry_date', '<', $dateFrom);
                })
                ->get(['debit', 'credit']);

            $openingBalance = $this->signedSum($priorItems, $normalBalance);
        }

        $itemsQuery = JournalEntryItem::with('journalEntry')
            ->where('chart_of_account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('entry_status', 'posted');
                if ($dateFrom) {
                    $q->where('entry_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->where('entry_date', '<=', $dateTo);
                }
            });

        $items = $itemsQuery->get()->sortBy([
            fn ($a, $b) => $a->journalEntry->entry_date <=> $b->journalEntry->entry_date,
            fn ($a, $b) => $a->journal_entry_id <=> $b->journal_entry_id,
        ])->values();

        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        $lines = $items->map(function ($item) use (&$runningBalance, &$totalDebit, &$totalCredit, $normalBalance) {
            $debit = (float) $item->debit;
            $credit = (float) $item->credit;
            $totalDebit += $debit;
            $totalCredit += $credit;

            $signed = $normalBalance === 'debit' ? ($debit - $credit) : ($credit - $debit);
            $runningBalance += $signed;

            return [
                'entry_date' => $item->journalEntry->entry_date,
                'entry_number' => $item->journalEntry->entry_number,
                'reference' => $item->journalEntry->reference,
                'description' => $item->description ?: $item->journalEntry->narration,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
            ];
        });

        return [
            'opening_balance' => $openingBalance,
            'lines' => $lines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * Ending balance per postable account, as of `dateTo` (or across all
     * time if not given) — a lightweight overview, not a full Trial
     * Balance (that's a separate future report).
     */
    protected function buildAccountsSummary(Collection $accounts, ?string $dateTo): Collection
    {
        return $accounts->map(function ($account) use ($dateTo) {
            $items = JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateTo) {
                    $q->where('entry_status', 'posted');
                    if ($dateTo) {
                        $q->where('entry_date', '<=', $dateTo);
                    }
                })
                ->get(['debit', 'credit']);

            $totalDebit = $items->sum('debit');
            $totalCredit = $items->sum('credit');
            $balance = $this->signedSum($items, $account->normal_balance);

            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->account_type,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance' => $balance,
                'has_activity' => $items->isNotEmpty(),
            ];
        })->values();
    }

    /**
     * Sum a collection of debit/credit rows into one signed balance
     * figure, respecting the account's normal balance side.
     */
    protected function signedSum(Collection $items, string $normalBalance): float
    {
        $debit = (float) $items->sum('debit');
        $credit = (float) $items->sum('credit');

        return $normalBalance === 'debit' ? ($debit - $credit) : ($credit - $debit);
    }
}
