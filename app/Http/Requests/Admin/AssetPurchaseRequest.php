<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('asset_purchase')?->id;

        return [
            'asset_id' => [
                'required',
                'exists:assets,id',
                // App-level mirror of the DB's unique constraint, so the
                // admin gets a friendly message instead of a raw DB error.
                Rule::unique('asset_purchases', 'asset_id')->ignore($id),
            ],
            'vendor_id' => 'nullable|exists:vendors,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'invoice_number' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.unique' => 'This asset already has purchase information recorded — edit the existing record instead.',
            'warranty_expiry.after_or_equal' => 'Warranty expiry cannot fall before the purchase date.',
        ];
    }
}
