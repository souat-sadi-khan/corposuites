<?php

namespace App\Http\Requests\Admin;

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:assigned_date',
            'returned_date' => 'nullable|date|after_or_equal:assigned_date|required_if:assignment_status,returned',
            'assignment_status' => ['required', Rule::in(AssetAssignment::STATUSES)],
            'condition_on_assign' => ['required', Rule::in(AssetAssignment::CONDITIONS)],
            'condition_on_return' => ['nullable', Rule::in(AssetAssignment::CONDITIONS)],
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'returned_date.required_if' => 'A return date is required when the assignment is marked returned.',
            'expected_return_date.after_or_equal' => 'The expected return date cannot fall before the assignment date.',
            'returned_date.after_or_equal' => 'The return date cannot fall before the assignment date.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('assignment_status') !== 'assigned' || ! $this->input('asset_id')) {
                return;
            }

            $id = $this->route('asset_assignment')?->id;

            // A physical asset can only be in one person's hands at a time,
            // so an open assignment blocks another. Not expressible as a DB
            // constraint (it depends on the enum value of *other* rows), so
            // it lives here — the same app-level cross-record guard
            // `ProductVariantRequest`'s duplicate-combination check uses.
            $alreadyOut = AssetAssignment::where('asset_id', $this->input('asset_id'))
                ->where('assignment_status', 'assigned')
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($alreadyOut) {
                $asset = Asset::find($this->input('asset_id'));

                $validator->errors()->add(
                    'asset_id',
                    'Asset ' . ($asset->asset_code ?? '') . ' is already assigned to someone. Mark the existing assignment returned first.'
                );
            }
        });
    }
}
