<?php

namespace App\Http\Requests\Admin;

use App\Models\PaymentMake;
use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentMakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'finance_bank_account_id' => ['nullable', 'exists:finance_bank_accounts,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(PaymentMake::METHODS)],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_invoice_id' => ['required', 'distinct', 'exists:purchase_invoices,id'],
            'items.*.amount_allocated' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $vendorId = $this->input('vendor_id');
            $paymentMake = $this->route('payment_make');
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $invoiceId = $item['purchase_invoice_id'] ?? null;
                $amount = (float) ($item['amount_allocated'] ?? 0);

                if (!$invoiceId) {
                    continue;
                }

                $invoice = PurchaseInvoice::find($invoiceId);

                if (!$invoice) {
                    continue;
                }

                if ($vendorId && (int) $invoice->vendor_id !== (int) $vendorId) {
                    $validator->errors()->add(
                        "items.{$index}.purchase_invoice_id",
                        "Invoice {$invoice->invoice_number} does not belong to the selected vendor."
                    );
                    continue;
                }

                $alreadyAllocatedByThisPayment = 0;
                if ($paymentMake) {
                    $alreadyAllocatedByThisPayment = $paymentMake->items()
                        ->where('purchase_invoice_id', $invoiceId)
                        ->sum('amount_allocated');
                }

                $availableBalance = round($invoice->balance_due + (float) $alreadyAllocatedByThisPayment, 2);

                if ($amount > $availableBalance + 0.01) {
                    $validator->errors()->add(
                        "items.{$index}.amount_allocated",
                        "Amount allocated to invoice {$invoice->invoice_number} exceeds its available balance of {$availableBalance}."
                    );
                }
            }
        });
    }
}
