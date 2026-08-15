<?php

namespace App\Http\Requests\Admin;

use App\Models\Asset;
use App\Models\AssetDisposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('asset_disposal')?->id;

        return [
            'asset_id' => [
                'required',
                'exists:assets,id',
                // App-level mirror of the DB's unique constraint, so the
                // admin gets a friendly message rather than a raw DB error.
                Rule::unique('asset_disposals', 'asset_id')->ignore($id),
            ],
            'disposal_date' => 'required|date',
            'disposal_method' => ['required', Rule::in(AssetDisposal::METHODS)],
            'recipient' => 'nullable|string|max:255',
            'proceeds' => 'nullable|numeric|min:0',
            'disposal_status' => ['required', Rule::in(AssetDisposal::STATUSES)],
            'approved_by' => 'nullable|exists:admins,id',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
        // book_value_at_disposal and gain_loss are absent — both are
        // snapshotted by the service, never submitted.
    }

    public function messages(): array
    {
        return [
            'asset_id.unique' => 'This asset already has a disposal record — edit the existing record instead.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->input('asset_id') || ! $this->input('disposal_date')) {
                return;
            }

            $asset = Asset::with('assetPurchase')->find($this->input('asset_id'));

            // An asset cannot be disposed of before it was bought.
            if ($asset?->assetPurchase
                && $asset->assetPurchase->purchase_date->gt($this->input('disposal_date'))) {
                $validator->errors()->add(
                    'disposal_date',
                    'The disposal date cannot fall before the asset was purchased ('
                        . $asset->assetPurchase->purchase_date->format('d M, Y') . ').'
                );
            }
        });
    }
}
