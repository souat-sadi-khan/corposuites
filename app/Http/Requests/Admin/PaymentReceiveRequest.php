<?php

namespace App\Http\Requests\Admin;

use App\Models\PaymentReceive;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'finance_bank_account_id' => ['nullable', 'exists:finance_bank_accounts,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(PaymentReceive::METHODS)],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_id' => ['required', 'distinct', 'exists:sales_invoices,id'],
            'items.*.amount_allocated' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Cross-field checks not expressible as plain rules:
     *
     * (1) Every allocated invoice must actually belong to the selected
     *     customer — an allocation against someone else's invoice would
     *     silently misapply that customer's payment.
     * (2) No allocation may exceed the invoice's currently available
     *     balance. On edit, this Payment Receive's own prior allocation
     *     to that same invoice (about to be reversed and recreated) is
     *     added back as headroom, so re-submitting the exact same
     *     allocation on an edit is never incorrectly rejected as
     *     "exceeding balance".
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $customerId = $this->input('customer_id');
            $paymentReceive = $this->route('payment_receive');
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $invoiceId = $item['sales_invoice_id'] ?? null;
                $amount = (float) ($item['amount_allocated'] ?? 0);

                if (!$invoiceId) {
                    continue;
                }

                $invoice = SalesInvoice::find($invoiceId);

                if (!$invoice) {
                    continue;
                }

                if ($customerId && (int) $invoice->customer_id !== (int) $customerId) {
                    $validator->errors()->add(
                        "items.{$index}.sales_invoice_id",
                        "Invoice {$invoice->invoice_number} does not belong to the selected customer."
                    );
                    continue;
                }

                $alreadyAllocatedByThisPayment = 0;
                if ($paymentReceive) {
                    $alreadyAllocatedByThisPayment = $paymentReceive->items()
                        ->where('sales_invoice_id', $invoiceId)
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
