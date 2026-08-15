<?php

namespace App\Services;

use App\Models\PaymentReceive;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;

class PaymentReceiveService
{
    /**
     * Create a Payment Receive: writes the header + allocation rows, and
     * applies each allocated amount onto the referenced Sales Invoice's
     * `amount_paid`, deriving its `invoice_status` from the resulting
     * balance. Same "one module's service reaches into another module's
     * table to update it" pattern already established by
     * `BankReconciliationService`/`SupplierQuotationService`, applied here
     * to `SalesInvoice.amount_paid`/`invoice_status` instead of
     * `BankTransaction.reconciled`/`SupplierQuotation.quotation_status`.
     */
    public function create(array $data): PaymentReceive
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['payment_number'] = $this->generatePaymentNumber();
            $data['amount'] = $this->calculateTotal($items);

            $paymentReceive = PaymentReceive::create($data);

            $this->applyItems($paymentReceive, $items);

            return $paymentReceive;
        });
    }

    /**
     * Update a Payment Receive: first reverses every previously-applied
     * allocation (undoing its effect on each invoice's `amount_paid`/
     * `invoice_status`) before deleting the old allocation rows and
     * re-applying the newly submitted set — the same "unmark, delete,
     * recreate, re-mark" sequence `BankReconciliationService::update()`
     * already established for `BankTransaction.reconciled`.
     */
    public function update(PaymentReceive $paymentReceive, array $data): PaymentReceive
    {
        return DB::transaction(function () use ($paymentReceive, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $this->reverseItems($paymentReceive);
            $paymentReceive->items()->delete();

            $data['amount'] = $this->calculateTotal($items);
            $paymentReceive->update($data);

            $this->applyItems($paymentReceive, $items);

            return $paymentReceive->fresh();
        });
    }

    /**
     * Reverse every allocation this Payment Receive had applied, then
     * delete the header (items cascade via FK).
     */
    public function delete(PaymentReceive $paymentReceive): void
    {
        DB::transaction(function () use ($paymentReceive) {
            $this->reverseItems($paymentReceive);
            $paymentReceive->delete();
        });
    }

    protected function calculateTotal(array $items): float
    {
        return round(collect($items)->sum(fn ($item) => (float) ($item['amount_allocated'] ?? 0)), 2);
    }

    protected function applyItems(PaymentReceive $paymentReceive, array $items): void
    {
        foreach ($items as $item) {
            $invoice = SalesInvoice::find($item['sales_invoice_id']);
            if (!$invoice) {
                continue;
            }

            $paymentReceive->items()->create([
                'sales_invoice_id' => $invoice->id,
                'amount_allocated' => $item['amount_allocated'],
            ]);

            $invoice->amount_paid = round((float) $invoice->amount_paid + (float) $item['amount_allocated'], 2);
            $this->deriveInvoiceStatus($invoice);
        }
    }

    protected function reverseItems(PaymentReceive $paymentReceive): void
    {
        foreach ($paymentReceive->items as $allocation) {
            $invoice = $allocation->salesInvoice;
            if (!$invoice) {
                continue;
            }

            $invoice->amount_paid = max(0, round((float) $invoice->amount_paid - (float) $allocation->amount_allocated, 2));
            $this->deriveInvoiceStatus($invoice);
        }
    }

    /**
     * Recompute an invoice's `invoice_status` from its own
     * grand_total/amount_paid, never touching a `cancelled` invoice — a
     * cancelled invoice was never actually billed, so no amount of
     * payment activity against it should resurrect its status.
     */
    protected function deriveInvoiceStatus(SalesInvoice $invoice): void
    {
        if ($invoice->invoice_status === 'cancelled') {
            $invoice->save();
            return;
        }

        $balance = round((float) $invoice->grand_total - (float) $invoice->amount_paid, 2);

        if ($balance <= 0) {
            $invoice->invoice_status = 'paid';
        } elseif ($invoice->amount_paid > 0) {
            $invoice->invoice_status = 'partially_paid';
        } else {
            $invoice->invoice_status = 'sent';
        }

        $invoice->save();
    }

    protected function generatePaymentNumber(): string
    {
        $lastId = (int) PaymentReceive::max('id');
        return 'PMT-' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }
}
