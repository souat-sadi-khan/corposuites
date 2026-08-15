<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AccountsPayableController extends Controller
{
    /**
     * Display Accounts Payable — the vendor-side mirror of Accounts
     * Receivable: a per-vendor outstanding-balance view built directly
     * from Purchase Invoices, not from Journal Entries. Same "controller
     * only, no new table/Model/Service" shape as every prior read-only
     * report in this project — reads `PurchaseInvoice` rows directly
     * (which already carry `grand_total`/`amount_paid`/`balance_due`),
     * rather than posting through the Chart of Accounts/Journal Entries
     * layer.
     *
     * Cancelled invoices are excluded from every figure — a cancelled
     * invoice was never actually billed, so it has no place in a payable
     * balance, same "exclude cancelled" convention Accounts Receivable
     * and every prior Sales/Purchase report already use.
     *
     * With no vendor selected, shows a summary (total invoiced, total
     * paid, outstanding balance, and overdue count) per vendor with any
     * invoice activity. Selecting one vendor drills into the full list of
     * their individual invoices with an overdue flag per invoice (a
     * `balance_due > 0` invoice whose `due_date` is in the past).
     */
    public function index(Request $request)
    {
        $vendorId = $request->vendor_id;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $vendors = Vendor::active()->orderBy('name')->get();

        $selectedVendor = null;
        $ledger = null;
        $summary = null;

        if ($vendorId) {
            $selectedVendor = $vendors->firstWhere('id', (int) $vendorId) ?? Vendor::find($vendorId);

            if ($selectedVendor) {
                $ledger = $this->buildVendorLedger($selectedVendor, $dateFrom, $dateTo);
            }
        } else {
            $summary = $this->buildVendorsSummary($vendors, $dateFrom, $dateTo);
        }

        $totalOutstanding = $summary ? $summary->sum('balance_due') : null;

        return view('admin.accounts-payable.index', compact(
            'vendors',
            'vendorId',
            'dateFrom',
            'dateTo',
            'selectedVendor',
            'ledger',
            'summary',
            'totalOutstanding'
        ));
    }

    /**
     * Build the full invoice list for one vendor within the given date
     * range (filtered on `invoice_date`), each with its own balance due
     * and an overdue flag, plus header totals.
     */
    protected function buildVendorLedger(Vendor $vendor, ?string $dateFrom, ?string $dateTo): array
    {
        $invoices = PurchaseInvoice::where('vendor_id', $vendor->id)
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
     * vendor, within the given date range.
     */
    protected function buildVendorsSummary(Collection $vendors, ?string $dateFrom, ?string $dateTo): Collection
    {
        return $vendors->map(function ($vendor) use ($dateFrom, $dateTo) {
            $invoices = PurchaseInvoice::where('vendor_id', $vendor->id)
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
                'vendor_id' => $vendor->id,
                'name' => $vendor->name,
                'vendor_code' => $vendor->vendor_code,
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'overdue_count' => $overdueCount,
                'has_activity' => $invoices->isNotEmpty(),
            ];
        })->filter(fn ($row) => $row['has_activity'])->values();
    }
}
