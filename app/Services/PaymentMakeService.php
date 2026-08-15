<?php

namespace App\Services;

use App\Models\PaymentMake;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

class PaymentMakeService
{
    public function create(array $data): PaymentMake
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['payment_number'] = $this->generatePaymentNumber();
            $data['amount'] = $this->calculateTotal($items);

            $paymentMake = PaymentMake::create($data);

            $this->applyItems($paymentMake, $items);

            return $paymentMake;
        });
    }

    public function update(PaymentMake $paymentMake, array $data): PaymentMake
    {
        return DB::transaction(function () use ($paymentMake, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $this->reverseItems($paymentMake);
            $paymentMake->items()->delete();

            $data['amount'] = $this->calculateTotal($items);
            $paymentMake->update($data);

            $this->applyItems($paymentMake, $items);

            return $paymentMake->fresh();
        });
    }

    public function delete(PaymentMake $paymentMake): void
    {
        DB::transaction(function () use ($paymentMake) {
            $this->reverseItems($paymentMake);
            $paymentMake->delete();
        });
    }

    protected function calculateTotal(array $items): float
    {
        return round(collect($items)->sum(fn ($item) => (float) ($item['amount_allocated'] ?? 0)), 2);
    }

    protected function applyItems(PaymentMake $paymentMake, array $items): void
    {
        foreach ($items as $item) {
            $invoice = PurchaseInvoice::find($item['purchase_invoice_id']);
            if (!$invoice) {
                continue;
            }

            $paymentMake->items()->create([
                'purchase_invoice_id' => $invoice->id,
                'amount_allocated' => $item['amount_allocated'],
            ]);

            $invoice->amount_paid = round((float) $invoice->amount_paid + (float) $item['amount_allocated'], 2);
            $this->deriveInvoiceStatus($invoice);
        }
    }

    protected function reverseItems(PaymentMake $paymentMake): void
    {
        foreach ($paymentMake->items as $allocation) {
            $invoice = $allocation->purchaseInvoice;
            if (!$invoice) {
                continue;
            }

            $invoice->amount_paid = max(0, round((float) $invoice->amount_paid - (float) $allocation->amount_allocated, 2));
            $this->deriveInvoiceStatus($invoice);
        }
    }

    protected function deriveInvoiceStatus(PurchaseInvoice $invoice): void
    {
        if ($invoice->invoice_status === 'cancelled') {
            $invoice->save();
            return;
        }

        $balance = round((float) $invoice->grand_total - (float) $invoice->amount_paid, 2);

        if ($balance <= 0) {
            $invoice->invoice_status = 'paid';
        } elseif ($invoice->amount_paid > 0) {
            $invoice->invoice_status = 'approved';
        } else {
            $invoice->invoice_status = 'pending';
        }

        $invoice->save();
    }

    protected function generatePaymentNumber(): string
    {
        $lastId = (int) PaymentMake::max('id');
        return 'PMK-' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }
}
