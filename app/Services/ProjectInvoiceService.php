<?php

namespace App\Services;

use App\Models\ProjectInvoice;
use App\Models\ProjectInvoiceItem;
use RuntimeException;

class ProjectInvoiceService
{
    public function create(array $data): ProjectInvoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data = $this->withComputedTotals($data, $items);
        $data['invoice_number'] = $this->generateInvoiceNumber();

        $invoice = ProjectInvoice::create($data);
        $this->syncItems($invoice, $items);

        return $invoice->fresh();
    }

    public function update(ProjectInvoice $invoice, array $data): ProjectInvoice
    {
        if (in_array($invoice->invoice_status, ProjectInvoice::CLOSED_STATUSES, true)) {
            throw new RuntimeException('A ' . $invoice->invoice_status . ' invoice can no longer be edited.');
        }

        $items = $data['items'] ?? [];
        unset($data['items'], $data['invoice_number']);

        $data = $this->withComputedTotals($data, $items);

        $invoice->update($data);
        $this->syncItems($invoice, $items);

        return $invoice->fresh();
    }

    /**
     * A plain model delete — items cascade via FK. Since a "billed" entry/
     * expense is only ever recognized through a still-linked item on a
     * non-cancelled invoice (see ProjectTimeEntry::is_locked/
     * ProjectExpense::is_invoiced), deleting the invoice here automatically
     * frees every source it had billed, with no separate unlink step.
     */
    public function delete(ProjectInvoice $invoice): bool
    {
        return $invoice->delete();
    }

    public function markSent(ProjectInvoice $invoice): ProjectInvoice
    {
        if ($invoice->invoice_status !== 'draft') {
            throw new RuntimeException('Only a draft invoice can be marked as sent.');
        }

        $invoice->update(['invoice_status' => 'sent']);

        return $invoice->fresh();
    }

    /**
     * Record a payment against this invoice's balance — a single running
     * total, not a per-invoice allocation ledger (Sales' Payment Receive
     * exists for that shape against Sales Invoices; building a second one
     * here for a single project-billing checklist item would have been
     * over-engineering). The status is re-derived from the new balance on
     * every call, so it can never drift from what amount_paid actually is.
     */
    public function recordPayment(ProjectInvoice $invoice, float $amount): ProjectInvoice
    {
        if (! in_array($invoice->invoice_status, ['sent', 'partially_paid'], true)) {
            throw new RuntimeException('Only a sent invoice can take a payment — mark it sent first.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Enter a payment amount greater than zero.');
        }

        if ($amount > $invoice->balance_due) {
            throw new RuntimeException('That exceeds the remaining balance of ' . number_format($invoice->balance_due, 2) . '.');
        }

        $newAmountPaid = round((float) $invoice->amount_paid + $amount, 2);
        $newBalance = round((float) $invoice->grand_total - $newAmountPaid, 2);

        $invoice->update([
            'amount_paid' => $newAmountPaid,
            'invoice_status' => $newBalance <= 0 ? 'paid' : 'partially_paid',
        ]);

        return $invoice->fresh();
    }

    /**
     * Cancelling — not deleting — is the normal way to void a mistaken bill
     * while keeping it on record. A paid invoice cannot be cancelled; undo
     * that by recording a correction elsewhere rather than erasing the
     * fact that money changed hands. Cancelling implicitly frees every
     * source this invoice had billed, the same way delete() does.
     */
    public function cancel(ProjectInvoice $invoice): ProjectInvoice
    {
        if ($invoice->invoice_status === 'paid') {
            throw new RuntimeException('A fully paid invoice cannot be cancelled.');
        }

        if ($invoice->invoice_status === 'cancelled') {
            throw new RuntimeException('This invoice is already cancelled.');
        }

        $invoice->update(['invoice_status' => 'cancelled']);

        return $invoice->fresh();
    }

    /**
     * Subtotal is always the fresh sum of the submitted line items — never
     * trusted from client input. grand_total is floored at zero so an
     * overzealous discount can never produce a nonsensical negative bill.
     */
    protected function withComputedTotals(array $data, array $items): array
    {
        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0));

        $discount = (float) ($data['discount_amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);

        $data['subtotal'] = round($subtotal, 2);
        $data['grand_total'] = max(0, round($subtotal - $discount + $tax, 2));

        return $data;
    }

    /**
     * Delete-then-recreate, the same convention every master-detail module
     * in this project uses for its line items.
     */
    protected function syncItems(ProjectInvoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            ProjectInvoiceItem::create([
                'project_invoice_id' => $invoice->id,
                'source_type' => $item['source_type'] ?? 'manual',
                'project_time_entry_id' => $item['project_time_entry_id'] ?? null,
                'project_expense_id' => $item['project_expense_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ]);
        }
    }

    protected function generateInvoiceNumber(): string
    {
        $lastId = ProjectInvoice::max('id') ?? 0;

        return 'PBI-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
