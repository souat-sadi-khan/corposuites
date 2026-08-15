<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetMaintenanceRecord;
use App\Models\AssetMaintenanceSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'asset_maintenance_schedule_id' => 'nullable|exists:asset_maintenance_schedules,id',
            'title' => 'required|string|max:255',
            'maintenance_type' => ['required', Rule::in(AssetMaintenanceRecord::MAINTENANCE_TYPES)],
            'performed_date' => 'required|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'performed_by' => 'nullable|exists:employees,id',
            'cost' => 'nullable|numeric|min:0',
            'downtime_hours' => 'nullable|numeric|min:0',
            'record_status' => ['required', Rule::in(AssetMaintenanceRecord::STATUSES)],
            'work_done' => 'nullable|string',
            'findings' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $scheduleId = $this->input('asset_maintenance_schedule_id');

            if (! $scheduleId || ! $this->input('asset_id')) {
                return;
            }

            // A job cannot be logged against a schedule belonging to a
            // different asset — that would silently move the wrong
            // schedule's next due date.
            $schedule = AssetMaintenanceSchedule::find($scheduleId);

            if ($schedule && (int) $schedule->asset_id !== (int) $this->input('asset_id')) {
                $validator->errors()->add(
                    'asset_maintenance_schedule_id',
                    'That schedule belongs to a different asset. Pick a schedule for the selected asset, or leave it blank for unplanned work.'
                );
            }
        });
    }
}
