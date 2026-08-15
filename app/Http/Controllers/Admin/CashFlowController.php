<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CashFlowController extends Controller
{
    /**
     * Display the Cash Flow statement — every movement of cash over a
     * period, classified into Operating, Investing and Financing
     * activities, reconciling an opening cash balance to a closing one.
     *
     * Pure read-only report: no new table/Model/Service/Request, same
     * "controller only" shape as every prior Accounting report. Per this
     * project's established precedent (documented in Stock Valuation's,
     * Low Stock Alerts', General Ledger's, Trial Balance's, Profit and
     * Loss's and Balance Sheet's own changelog entries), this controller
     * computes its own aggregation independently rather than sharing a
     * service.
     *
     * **Direct method, not indirect.** An indirect-method statement starts
     * from net profit and adjusts for non-cash items and working-capital
     * movements — that requires knowing which balance-sheet movements are
     * working capital, which this project has no data to distinguish
     * (there is no "current vs non-current" flag on Chart of Accounts).
     * The direct method reads actual cash movements straight from the
     * journal, which this data model supports exactly, so it is both the
     * more accurate and the more honest choice here.
     *
     * A "cash account" is identified by the same heuristic Cash Book
     * established (a postable, `asset`-natured account whose assigned
     * Account Type is named something containing "cash" or "bank"), with
     * the same honest empty state when none has been configured.
     *
     * Only `entry_status = 'posted'` journal entries are read — the same
     * stricter-than-Inventory filter every prior Accounting report uses.
     */
    public function index(Request $request)
    {
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $cashAccounts = ChartOfAccount::active()
            ->with('accountType')
            ->where('is_group', false)
            ->where('account_type', 'asset')
            ->whereHas('accountType', function ($q) {
                $q->where('name', 'like', '%cash%')->orWhere('name', 'like', '%bank%');
            })
            ->orderBy('code')
            ->get();

        $cashAccountIds = $cashAccounts->pluck('id')->all();

        $openingBalance = 0.0;
        $lines = collect();
        $unclassified = 0.0;

        if (! empty($cashAccountIds)) {
            $openingBalance = $this->cashBalanceBefore($cashAccountIds, $dateFrom);
            [$lines, $unclassified] = $this->buildFlows($cashAccountIds, $dateFrom, $dateTo);
        }

        $operating = $lines->where('activity', 'operating')->values();
        $investing = $lines->where('activity', 'investing')->values();
        $financing = $lines->where('activity', 'financing')->values();

        $totalOperating = round($operating->sum('amount'), 2);
        $totalInvesting = round($investing->sum('amount'), 2);
        $totalFinancing = round($financing->sum('amount'), 2);

        $netChange = round($totalOperating + $totalInvesting + $totalFinancing, 2);
        $closingBalance = round($openingBalance + $netChange, 2);

        // Independent cross-check: the closing figure derived by summing
        // classified flows must equal the cash accounts' own raw balance at
        // the cut-off date. A mismatch means a cash movement escaped
        // classification, so it is surfaced rather than hidden.
        $actualClosing = empty($cashAccountIds)
            ? 0.0
            : $this->cashBalanceAsOf($cashAccountIds, $dateTo);

        $reconciliationDifference = round($closingBalance - $actualClosing, 2);
        $isReconciled = abs($reconciliationDifference) < 0.005;

        return view('admin.cash-flow.index', compact(
            'cashAccounts',
            'dateFrom',
            'dateTo',
            'operating',
            'investing',
            'financing',
            'totalOperating',
            'totalInvesting',
            'totalFinancing',
            'openingBalance',
            'netChange',
            'closingBalance',
            'actualClosing',
            'reconciliationDifference',
            'isReconciled',
            'unclassified'
        ));
    }

    /**
     * Raw cash balance across every cash account strictly before the
     * period start (the opening balance carried into the statement).
     */
    protected function cashBalanceBefore(array $cashAccountIds, ?string $dateFrom): float
    {
        if (! $dateFrom) {
            return 0.0;
        }

        $items = JournalEntryItem::whereIn('chart_of_account_id', $cashAccountIds)
            ->whereHas('journalEntry', function ($q) use ($dateFrom) {
                $q->where('entry_status', 'posted')->where('entry_date', '<', $dateFrom);
            })
            ->get(['debit', 'credit']);

        return round((float) $items->sum('debit') - (float) $items->sum('credit'), 2);
    }

    /**
     * Raw cash balance across every cash account up to and including the
     * cut-off date — used only as an independent check against the
     * classified-flows total.
     */
    protected function cashBalanceAsOf(array $cashAccountIds, ?string $dateTo): float
    {
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
     * Classify every cash movement in the period by looking at what the
     * cash was exchanged *for* — the contra (non-cash) accounts on the
     * same journal entry:
     *
     *   revenue / expense       → Operating
     *   asset (other than cash) → Investing
     *   liability / equity      → Financing
     *
     * For a compound entry with several contra accounts, the entry's net
     * cash movement is split across them in proportion to each contra
     * line's own magnitude, so a single entry can legitimately contribute
     * to more than one activity. Pure cash-to-cash transfers (an entry
     * whose only lines are cash accounts) net to zero and are skipped.
     *
     * Movements are grouped per contra account so the statement reads as
     * a short list of named sources and uses of cash rather than one row
     * per journal line.
     */
    protected function buildFlows(array $cashAccountIds, ?string $dateFrom, ?string $dateTo): array
    {
        $entries = JournalEntry::with('items.chartOfAccount')
            ->where('entry_status', 'posted')
            ->when($dateFrom, fn ($q) => $q->where('entry_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('entry_date', '<=', $dateTo))
            ->whereHas('items', fn ($q) => $q->whereIn('chart_of_account_id', $cashAccountIds))
            ->get();

        $buckets = [];
        $unclassified = 0.0;

        foreach ($entries as $entry) {
            $cashNet = 0.0;
            $contras = [];

            foreach ($entry->items as $item) {
                $net = (float) $item->debit - (float) $item->credit;

                if (in_array($item->chart_of_account_id, $cashAccountIds, true)) {
                    $cashNet += $net;
                } elseif ($item->chartOfAccount) {
                    $contras[] = ['account' => $item->chartOfAccount, 'weight' => abs($net)];
                }
            }

            $cashNet = round($cashNet, 2);

            if (abs($cashNet) < 0.005) {
                continue; // cash-to-cash transfer, or no net cash movement
            }

            $totalWeight = array_sum(array_column($contras, 'weight'));

            if ($totalWeight <= 0) {
                // Cash moved but there is nothing to attribute it to.
                $unclassified += $cashNet;
                continue;
            }

            foreach ($contras as $contra) {
                $share = round($cashNet * ($contra['weight'] / $totalWeight), 2);

                if (abs($share) < 0.005) {
                    continue;
                }

                $account = $contra['account'];
                $activity = $this->classify($account->account_type);
                $key = $activity . '|' . $account->id;

                if (! isset($buckets[$key])) {
                    $buckets[$key] = [
                        'activity' => $activity,
                        'account_id' => $account->id,
                        'code' => $account->code,
                        'name' => $account->name,
                        'account_type' => $account->account_type,
                        'amount' => 0.0,
                    ];
                }

                $buckets[$key]['amount'] += $share;
            }
        }

        $lines = collect(array_values($buckets))
            ->map(function ($row) {
                $row['amount'] = round($row['amount'], 2);

                return $row;
            })
            ->filter(fn ($row) => abs($row['amount']) > 0.001)
            ->sortBy('code')
            ->values();

        return [$lines, round($unclassified, 2)];
    }

    /**
     * Map a contra account's type onto a cash-flow activity. Revenue and
     * expense are day-to-day trading (operating); buying or selling other
     * assets is investing; liabilities and equity are how the business is
     * funded (financing).
     */
    protected function classify(string $accountType): string
    {
        return match ($accountType) {
            'revenue', 'expense' => 'operating',
            'asset' => 'investing',
            'liability', 'equity' => 'financing',
            default => 'operating',
        };
    }
}
