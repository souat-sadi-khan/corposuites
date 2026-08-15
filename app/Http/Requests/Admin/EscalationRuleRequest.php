<?php

namespace App\Http\Requests\Admin;

use App\Models\EscalationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EscalationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('escalation_rule')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('escalation_rules', 'name')->ignore($id)],
            'priority' => ['required', Rule::in(EscalationRule::PRIORITIES)],
            'trigger' => ['required', Rule::in(EscalationRule::TRIGGERS)],
            'escalate_to_admin_id' => 'nullable|exists:admins,id',
            'escalate_priority_to' => ['nullable', Rule::in(EscalationRule::PRIORITIES)],
            'description' => 'nullable|string|max:1000',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An escalation rule with this name already exists.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $id = $this->route('escalation_rule')?->id;

            // One rule per (priority, trigger) pair — a ticket's applicable
            // rule must always be an unambiguous single lookup. Not
            // expressible as a plain Rule::unique() since it spans two
            // columns together; the DB's own composite unique index is the
            // ultimate guard, this is just the friendly message ahead of it.
            $exists = EscalationRule::where('priority', $this->priority)
                ->where('trigger', $this->trigger)
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'trigger',
                    'A rule already exists for ' . ucfirst((string) $this->priority) . ' priority ' . str_replace('_', ' ', (string) $this->trigger) . ' — edit the existing one instead.'
                );
            }

            if (! $this->escalate_to_admin_id && ! $this->escalate_priority_to) {
                $validator->errors()->add(
                    'escalate_to_admin_id',
                    'Set at least one action — reassign to an agent, bump the priority, or both.'
                );
            }
        });
    }
}
