<?php

namespace App\Http\Requests\Admin;

use App\Models\SalesTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesTargetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('sales_target') ? $this->route('sales_target')->id : null;

        return [
            'admin_id' => ['required', 'exists:admins,id'],
            'period_type' => ['required', Rule::in(SalesTarget::PERIOD_TYPES)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->admin_id || !$this->period_start || !$this->period_end) {
                return;
            }

            $exists = SalesTarget::where('admin_id', $this->admin_id)
                ->where('period_start', $this->period_start)
                ->where('period_end', $this->period_end)
                ->when($this->route('sales_target'), function ($q) {
                    $q->where('id', '!=', $this->route('sales_target')->id);
                })
                ->exists();

            if ($exists) {
                $validator->errors()->add('period_start', 'This salesperson already has a target for the exact same period.');
            }
        });
    }
}
