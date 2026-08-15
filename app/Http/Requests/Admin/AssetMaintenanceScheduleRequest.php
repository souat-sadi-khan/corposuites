<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetMaintenanceSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetMaintenanceScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'maintenance_type' => ['required', Rule::in(AssetMaintenanceSchedule::MAINTENANCE_TYPES)],
            'frequency' => ['required', Rule::in(AssetMaintenanceSchedule::FREQUENCIES)],
            'start_date' => 'required|date',
            'last_performed_date' => 'nullable|date|after_or_equal:start_date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'assigned_to' => 'nullable|exists:employees,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'schedule_status' => ['required', Rule::in(AssetMaintenanceSchedule::STATUSES)],
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
        // next_due_date is deliberately absent — always derived by the
        // service from frequency + start/last-performed date.
    }

    public function messages(): array
    {
        return [
            'last_performed_date.after_or_equal' => 'The last performed date cannot fall before the schedule start date.',
        ];
    }
}
