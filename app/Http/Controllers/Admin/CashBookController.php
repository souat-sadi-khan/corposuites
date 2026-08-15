<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CashBookController extends Controller
{
    /**
     * Display the Cash Book — the same per-account running-balance
     * mechanics as the General Ledger, but scoped to only Cash/Bank
     * accounts and labelled in cash-book terms (Receipts/Payments instead
     * of Debit/Credit). No new input table: pure read-only report, same
     * "controller only" shape as General Ledger/Inventory Transactions/
     * Stock Valuation — the balance math is a self-contained copy of
     * `GeneralLedgerController`'s, per this project's established
     * precedent that each report computes its own aggregation
     * independently rather than sharing a service.
     *
     * A "cash account" here is identified by a heuristic, not a dedicated
     * column: a postable, `asset`-natured Chart of Accounts entry whose
     * assigned Account Type (the optional sub-classification retrofitted
     * in the previous session) has a name containing "cash" or "bank"
     * (case-insensitive). This is the same "match by label text" heuristic
     * already used and documented as a known limitation elsewhere in this
     * project (e.g. CRM's lead-conversion-rate metric matching on status
     * label) — if no Account Type named something like "Cash" or "Bank"
     * has been created and assigned to any account, this page will
     * legitimately show nothing to select, which is the correct, honest
     * state rather than a guess at which accounts are cash-like.
     */
    public function index(Request $request)
    {
        $accountId = $request->account_id;
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

        $selectedAccount = null;
        $book = null;
        $summary = null;

        if ($accountId) {
            $selectedAccount = $cashAccounts->firstWhere('id', (int) $accountId);

            if ($selectedAccount) {
                $book = $this->buildCashBook($selectedAccount, $dateFrom, $dateTo);
            }
        } else {
            $summary = $this->buildCashAccountsSummary($cashAccounts, $dateTo);
        }

        $totalCashBalance = $summary ? $summary->sum('balance') : null;

        return view('admin.cash-book.index', compact(
            'cashAccounts',
            'accountId',
            'dateFrom',
            'dateTo',
            'selectedAccount',
            'book',
            'summary',
            'totalCashBalance'
        ));
    }

    /**
     * Same running-balance mechanics as
     * `GeneralLedgerController::buildAccountLedger()`, renamed to cash-book
     * terms: `debit` → receipt (cash coming in), `credit` → payment (cash
     * going out). Every Cash Book account is asset-natured (enforced by
     * the `account_type = 'asset'` filter above), so the balance always
     * increases with a receipt and decreases with a payment — no
     * normal-balance branching needed here, unlike the General Ledger's
     * generic version which must handle any account type.
     */
    protected function buildCashBook(ChartOfAccount $account, ?string $dateFrom, ?string $dateTo): array
    {
        $openingBalance = 0;

        if ($dateFrom) {
            $priorItems = JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom) {
                    $q->where('entry_status', 'posted')->where('entry_date', '<', $dateFrom);
                })
                ->get(['debit', 'credit']);

            $openingBalance = (float) $priorItems->sum('debit') - (float) $priorItems->sum('credit');
        }

        $items = JournalEntryItem::with('journalEntry')
            ->where('chart_of_account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('entry_status', 'posted');
                if ($dateFrom) {
                    $q->where('entry_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->where('entry_date', '<=', $dateTo);
                }
            })
            ->get()
            ->sortBy([
                fn ($a, $b) => $a->journalEntry->entry_date <=> $b->journalEntry->entry_date,
                fn ($a, $b) => $a->journal_entry_id <=> $b->journal_entry_id,
            ])->values();

        $runningBalance = $openingBalance;
        $totalReceipts = 0;
        $totalPayments = 0;

        $lines = $items->map(function ($item) use (&$runningBalance, &$totalReceipts, &$totalPayments) {
            $receipt = (float) $item->debit;
            $payment = (float) $item->credit;
            $totalReceipts += $receipt;
            $totalPayments += $payment;
            $runningBalance += $receipt - $payment;

            return [
                'entry_date' => $item->journalEntry->entry_date,
                'entry_number' => $item->journalEntry->entry_number,
                'reference' => $item->journalEntry->reference,
                'description' => $item->description ?: $item->journalEntry->narration,
                'receipt' => $receipt,
                'payment' => $payment,
                'running_balance' => $runningBalance,
            ];
        });

        return [
            'opening_balance' => $openingBalance,
            'lines' => $lines,
            'total_receipts' => $totalReceipts,
            'total_payments' => $totalPayments,
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * Closing balance per cash/bank account, as of `dateTo` (or across all
     * time if not given).
     */
    protected function buildCashAccountsSummary(Collection $cashAccounts, ?string $dateTo): Collection
    {
        return $cashAccounts->map(function ($account) use ($dateTo) {
            $items = JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateTo) {
                    $q->where('entry_status', 'posted');
                    if ($dateTo) {
                        $q->where('entry_date', '<=', $dateTo);
                    }
                })
                ->get(['debit', 'credit']);

            $totalReceipts = (float) $items->sum('debit');
            $totalPayments = (float) $items->sum('credit');

            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type_name' => $account->accountType->name ?? '-',
                'total_receipts' => $totalReceipts,
                'total_payments' => $totalPayments,
                'balance' => $totalReceipts - $totalPayments,
            ];
        })->values();
    }
}
