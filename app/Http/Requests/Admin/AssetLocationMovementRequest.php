<?php

namespace App\Http\Requests\Admin;

use App\Models\Asset;
use App\Models\AssetLocationMovement;
use Illuminate\Foundation\Http\FormRequest;

class AssetLocationMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'asset_location_id' => 'required|exists:asset_locations,id',
            'moved_date' => 'required|date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
        // moved_by is set server-side from the authenticated admin.
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->input('asset_id') || ! $this->input('asset_location_id')) {
                return;
            }

            $id = $this->route('asset_location_movement')?->id;

            // Recording a move to the location the asset is already in adds
            // no information and would clutter the history, so it is
            // rejected — but only against the *latest* movement, since an
            // asset legitimately returns to a previous location over time.
            $latest = AssetLocationMovement::where('asset_id', $this->input('asset_id'))
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->orderBy('moved_date', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            if ($latest && (int) $latest->asset_location_id === (int) $this->input('asset_location_id')) {
                $asset = Asset::find($this->input('asset_id'));

                $validator->errors()->add(
                    'asset_location_id',
                    'Asset ' . ($asset->asset_code ?? '') . ' is already recorded at this location.'
                );
            }
        });
    }
}
