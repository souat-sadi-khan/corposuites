<?php

namespace App\Http\Requests\Admin;

use App\Models\SalesCommission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesCommissionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'admin_id' => ['required', 'exists:admins,id'],
            'period_type' => ['required', Rule::in(SalesCommission::PERIOD_TYPES)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'commission_rate' => ['required', 'numeric', 'min:0.01', 'max:100'],
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

            $exists = SalesCommission::where('admin_id', $this->admin_id)
                ->where('period_start', $this->period_start)
                ->where('period_end', $this->period_end)
                ->when($this->route('sales_commission'), function ($q) {
                    $q->where('id', '!=', $this->route('sales_commission')->id);
                })
                ->exists();

            if ($exists) {
                $validator->errors()->add('period_start', 'This salesperson already has a commission record for the exact same period.');
            }
        });
    }
}
