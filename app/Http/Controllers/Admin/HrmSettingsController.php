<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class HrmSettingsController extends Controller
{
    use ActivityLogger;

    public function index()
    {
        return view('admin.hrm-settings.index');
    }

    /**
     * A plain-language "how do I connect a device" guide, opened as a modal
     * from the HRM Settings page. Built with the ACTUAL live endpoint URL
     * and the currently configured token so it's a real, copy-paste-ready
     * example for this installation rather than a generic placeholder.
     */
    public function deviceGuide()
    {
        $endpointUrl = route('admin.attendance-device.punch');
        $token = get_settings('hrm_attendance_device_token');

        return view('admin.hrm-settings.device-guide', compact('endpointUrl', 'token'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Attendance & Time
            'leave_weekend_days' => 'required|string|max:20',
            'hrm_default_shift_start' => 'required|date_format:H:i',
            'hrm_default_shift_end' => 'required|date_format:H:i',
            'hrm_late_grace_minutes' => 'required|integer|min:0|max:240',
            'hrm_half_day_threshold_percent' => 'required|numeric|min:1|max:99',
            'hrm_geofence_required' => 'nullable|boolean',
            'hrm_office_latitude' => 'nullable|numeric|between:-90,90|required_if:hrm_geofence_required,1',
            'hrm_office_longitude' => 'nullable|numeric|between:-180,180|required_if:hrm_geofence_required,1',
            'hrm_geofence_radius_meters' => 'nullable|integer|min:10|max:5000',
            'hrm_attendance_device_token' => 'nullable|string|max:255',

            // Leave Management
            'hrm_leave_year_start_month' => 'required|integer|min:1|max:12',
            'hrm_leave_default_carry_forward_days' => 'nullable|numeric|min:0|max:365',

            // Payroll
            'hrm_payroll_cutoff_day' => 'nullable|integer|min:0|max:28',
        ], [
            'hrm_office_latitude.required_if' => 'Set the office location before turning on geofence validation.',
            'hrm_office_longitude.required_if' => 'Set the office location before turning on geofence validation.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please fix the highlighted fields.',
                'errors' => $validator->errors(),
            ]);
        }

        $settings = $validator->validated();

        // Checkbox fields are omitted from the request entirely when unchecked,
        // so they need an explicit false rather than being silently skipped.
        $settings['hrm_geofence_required'] = $request->boolean('hrm_geofence_required');

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'hrm',
                    'autoload' => true,
                ]
            );
        }

        Cache::forget('system_settings');

        $this->logActivity([
            'module' => 'hrm-settings',
            'action' => 'update',
            'description' => 'HRM configuration updated',
            'new_data' => $settings,
        ]);

        return response()->json([
            'status' => true,
            'load' => true,
            'message' => 'HRM settings updated successfully.',
        ]);
    }
}
