<?php

namespace App\Http\Requests\Admin;

use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseClaimRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'paid'])],
            'payment_date' => 'nullable|date',
            'reimbursement_method' => ['nullable', Rule::in(ExpenseClaim::REIMBURSEMENT_METHODS)],
            'status' => 'required|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $categoryId = $this->input('expense_category_id');
            if (!$categoryId) {
                return;
            }

            $category = ExpenseCategory::find($categoryId);
            if (!$category) {
                return;
            }

            $amount = (float) $this->input('amount');

            // Spending policy: reject a claim over the category's configured cap.
            if ($category->max_amount_per_claim !== null && $amount > (float) $category->max_amount_per_claim) {
                $validator->errors()->add(
                    'amount',
                    'This exceeds the maximum allowed for "' . $category->name . '" (limit: ' . number_format($category->max_amount_per_claim, 2) . ').'
                );
            }

            // Spending policy: require a receipt above the category's configured threshold.
            if ($category->receipt_required_above !== null && $amount > (float) $category->receipt_required_above) {
                $hasNewReceipt = $this->hasFile('receipt');
                $hasExistingReceipt = $this->route('expense_claim') && $this->route('expense_claim')->receipt_path;

                if (!$hasNewReceipt && !$hasExistingReceipt) {
                    $validator->errors()->add(
                        'receipt',
                        'A receipt is required for "' . $category->name . '" claims above ' . number_format($category->receipt_required_above, 2) . '.'
                    );
                }
            }
        });
    }
}
