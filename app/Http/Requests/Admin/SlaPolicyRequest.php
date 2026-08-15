<?php

namespace App\Http\Requests\Admin;

use App\Models\SlaPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('sla_policy')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('sla_policies', 'name')->ignore($id)],
            // Exactly one policy per priority bucket — a ticket's applicable
            // policy is always an unambiguous lookup, never a pick between
            // several candidates for the same priority.
            'priority' => ['required', Rule::in(SlaPolicy::PRIORITIES), Rule::unique('sla_policies', 'priority')->ignore($id)],
            'response_time_hours' => 'required|numeric|min:0.01|max:9999',
            'resolution_time_hours' => 'required|numeric|min:0.01|max:9999',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An SLA policy with this name already exists.',
            'priority.unique' => 'This priority already has an SLA policy — edit the existing one instead.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->response_time_hours || ! $this->resolution_time_hours) {
                return;
            }

            // A resolution can't be promised before the first response.
            if ((float) $this->resolution_time_hours < (float) $this->response_time_hours) {
                $validator->errors()->add(
                    'resolution_time_hours',
                    'Resolution time cannot be shorter than the response time.'
                );
            }
        });
    }
}
