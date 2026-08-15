<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AccountsReceivableController extends Controller
{
    /**
     * Display Accounts Receivable — a per-customer outstanding-balance view
     * built directly from Sales Invoices, not from Journal Entries. Same
     * "controller only, no new table/Model/Service" shape as General
     * Ledger/Cash Book, but simpler: there is no normal-balance branching
     * here (a customer's balance always just increases with what they're
     * invoiced and decreases with what they pay), so this reads
     * `SalesInvoice` rows directly rather than posting through the
     * Chart of Accounts/Journal Entries layer, the same way Sales/Purchase
     * Reports elsewhere in this project read their own module's documents
     * directly rather than via the accounting ledger.
     *
     * Cancelled invoices are excluded from every figure here — a cancelled
     * invoice was never actually billed, so it has no place in a
     * receivable balance, the same "exclude cancelled" convention already
     * used by every prior Sales/Purchase report in this project.
     *
     * With no customer selected, shows a summary (total invoiced, total
     * paid, outstanding balance, and overdue count) per customer with any
     * invoice activity. Selecting one customer drills into the full list
     * of their individual invoices with running balance and an overdue
     * flag per invoice (an invoice with `balance_due > 0` and `due_date`
     * in the past).
     */
    public function index(Request $request)
    {
        $customerId = $request->customer_id;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $customers = Customer::active()->orderBy('name')->get();

        $selectedCustomer = null;
        $ledger = null;
        $summary = null;

        if ($customerId) {
            $selectedCustomer = $customers->firstWhere('id', (int) $customerId) ?? Customer::find($customerId);

            if ($selectedCustomer) {
                $ledger = $this->buildCustomerLedger($selectedCustomer, $dateFrom, $dateTo);
            }
        } else {
            $summary = $this->buildCustomersSummary($customers, $dateFrom, $dateTo);
        }

        $totalOutstanding = $summary ? $summary->sum('balance_due') : null;

        return view('admin.accounts-receivable.index', compact(
            'customers',
            'customerId',
            'dateFrom',
            'dateTo',
            'selectedCustomer',
            'ledger',
            'summary',
            'totalOutstanding'
        ));
    }

    /**
     * Build the full invoice list for one customer within the given date
     * range (filtered on `invoice_date`), each with its own balance due and
     * an overdue flag, plus header totals.
     */
    protected function buildCustomerLedger(Customer $customer, ?string $dateFrom, ?string $dateTo): array
    {
        $invoices = SalesInvoice::where('customer_id', $customer->id)
            ->where('invoice_status', '!=', 'cancelled')
            ->when($dateFrom, fn ($q) => $q->where('invoice_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('invoice_date', '<=', $dateTo))
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $totalInvoiced = 0;
        $totalPaid = 0;
        $totalOutstanding = 0;
        $overdueCount = 0;

        $lines = $invoices->map(function ($invoice) use (&$totalInvoiced, &$totalPaid, &$totalOutstanding, &$overdueCount) {
            $balanceDue = $invoice->balance_due;
            $totalInvoiced += (float) $invoice->grand_total;
            $totalPaid += (float) $invoice->amount_paid;
            $totalOutstanding += $balanceDue;

            $isOverdue = $balanceDue > 0 && $invoice->due_date && $invoice->due_date->isPast();
            if ($isOverdue) {
                $overdueCount++;
            }

            return [
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $invoice->due_date,
                'invoice_number' => $invoice->invoice_number,
                'invoice_status' => $invoice->invoice_status,
                'grand_total' => (float) $invoice->grand_total,
                'amount_paid' => (float) $invoice->amount_paid,
                'balance_due' => $balanceDue,
                'is_overdue' => $isOverdue,
            ];
        });

        return [
            'lines' => $lines,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'overdue_count' => $overdueCount,
        ];
    }

    /**
     * Total invoiced / paid / outstanding and overdue-invoice count per
     * customer, within the given date range.
     */
    protected function buildCustomersSummary(Collection $customers, ?string $dateFrom, ?string $dateTo): Collection
    {
        return $customers->map(function ($customer) use ($dateFrom, $dateTo) {
            $invoices = SalesInvoice::where('customer_id', $customer->id)
                ->where('invoice_status', '!=', 'cancelled')
                ->when($dateFrom, fn ($q) => $q->where('invoice_date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->where('invoice_date', '<=', $dateTo))
                ->get(['grand_total', 'amount_paid', 'due_date']);

            $totalInvoiced = (float) $invoices->sum('grand_total');
            $totalPaid = (float) $invoices->sum('amount_paid');
            $balanceDue = round($totalInvoiced - $totalPaid, 2);

            $overdueCount = $invoices->filter(function ($invoice) {
                $due = round((float) $invoice->grand_total - (float) $invoice->amount_paid, 2);
                return $due > 0 && $invoice->due_date && $invoice->due_date->isPast();
            })->count();

            return [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'customer_code' => $customer->customer_code,
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'overdue_count' => $overdueCount,
                'has_activity' => $invoices->isNotEmpty(),
            ];
        })->filter(fn ($row) => $row['has_activity'])->values();
    }
}
