@extends('admin.layout.app', ['title' => 'HRM Setup', 'modal' => 'lg'])

@section('content')

    <form class="ajax_form settings-page" method="POST" action="{{ route('admin.hrm-settings.update') }}">
        @csrf
        @method('PUT')

        {{-- Attendance & Time --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Attendance &amp; Time</h5>
                    <p>Rules the Attendance Portal and device punch API apply on every check-in/check-out.</p>
                </div>

                <i class="ri-time-line"></i>
            </div>

            <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
                <i class="ri-information-line fs-5"></i>
                <div>
                    An employee with a <strong>Shift</strong> assigned is always judged against that shift's own
                    start/end time — not the fields below. The "Default Shift" fields here are only the fallback
                    used for employees with no shift assigned.
                    <a href="{{ route('admin.shifts.index') }}" class="ms-1">Manage Shifts &rarr;</a>
                </div>
            </div>

            <div class="row g-3 fm-body">
                <div class="col-md-6">
                    <label class="form-label">
                        Weekly Off Days
                    </label>

                    <select id="weekendDaysSelect" class="form-select select" multiple>
                        @php
                            $weekDays = [
                                0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                                4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
                            ];
                            $selectedWeekends = collect(explode(',', get_settings('leave_weekend_days', '5,6')))
                                ->filter(fn ($d) => $d !== '')
                                ->map(fn ($d) => (int) $d)
                                ->toArray();
                        @endphp

                        @foreach($weekDays as $num => $label)
                            <option value="{{ $num }}" {{ in_array($num, $selectedWeekends) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <input type="hidden" id="weekendDaysHidden" name="leave_weekend_days" value="{{ get_settings('leave_weekend_days', '5,6') }}">

                    <small class="text-muted">
                        Used by the attendance calendar and leave day counting.
                    </small>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Default Shift Start
                    </label>

                    <input type="time"
                            name="hrm_default_shift_start"
                            class="form-control"
                            value="{{ get_settings('hrm_default_shift_start', '09:00') }}"
                            required>

                    <small class="text-muted">
                        Fallback start time for employees with no shift assigned.
                    </small>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Default Shift End
                    </label>

                    <input type="time"
                            name="hrm_default_shift_end"
                            class="form-control"
                            value="{{ get_settings('hrm_default_shift_end', '18:00') }}"
                            required>

                    <small class="text-muted">
                        Fallback end time for employees with no shift assigned.
                    </small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Late Grace Period (minutes)
                    </label>

                    <input type="number"
                            step="1"
                            min="0"
                            max="240"
                            name="hrm_late_grace_minutes"
                            class="form-control"
                            value="{{ get_settings('hrm_late_grace_minutes', 15) }}"
                            required>

                    <small class="text-muted">
                        Minutes allowed past shift start (own shift, or the default above) before marking "Late".
                        Applies to every employee.
                    </small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Half-Day Threshold (%)
                    </label>

                    <input type="number"
                            step="1"
                            min="1"
                            max="99"
                            name="hrm_half_day_threshold_percent"
                            class="form-control"
                            value="{{ get_settings('hrm_half_day_threshold_percent', 50) }}"
                            required>

                    <small class="text-muted">
                        Checking out before working this % of the shift's duration (own shift, or the default
                        above) marks the day "Half Day". Applies to every employee.
                    </small>
                </div>

                <div class="col-md-8">
                    <label class="form-label">
                        Attendance Device Token
                    </label>

                    <div class="d-flex gap-2">
                        <input type="text"
                                id="deviceTokenInput"
                                name="hrm_attendance_device_token"
                                class="form-control"
                                value="{{ get_settings('hrm_attendance_device_token') }}"
                                placeholder="Shared secret for biometric/device punches"
                                autocomplete="off">

                        <button type="button" class="btn-nx-outline copy-token-btn flex-shrink-0" data-copy-target="#deviceTokenInput" title="Copy token">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>

                    <small class="text-muted">
                        Sent by devices in the <code>X-Attendance-Token</code> header when calling the punch API.
                    </small>
                </div>

                <div class="col-md-4 d-flex align-items-center">
                    <button type="button" id="openModal" data-url="{{ route('admin.hrm-settings.device-guide') }}" class="btn-nx-primary w-100">
                        <i class="ri-question-line"></i>
                        How to Connect a Device
                    </button>
                </div>
            </div>
        </div>

        {{-- Location Tracking --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Location Tracking (Geofencing)</h5>
                    <p>Optionally require check-in/check-out to happen near a specific office location.</p>
                </div>

                <i class="ri-map-pin-2-line"></i>
            </div>

            <div class="row g-3 fm-body">
                <div class="col-md-12">
                    <div class="form-check form-switch">
                        <input type="checkbox"
                                name="hrm_geofence_required"
                                class="form-check-input"
                                id="geofenceSwitch"
                                value="1"
                                {{ get_settings('hrm_geofence_required') ? 'checked' : '' }}>

                        <label class="form-check-label" for="geofenceSwitch">
                            Require Geofence Validation
                        </label>
                    </div>
                    <small class="text-muted">
                        When on, browser/mobile check-in and check-out (not device punches) are rejected if the
                        employee isn't within the radius below. If no office location is set, nothing is blocked.
                    </small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Office Latitude
                    </label>

                    <input type="number"
                            step="0.0000001"
                            min="-90"
                            max="90"
                            id="officeLatitude"
                            name="hrm_office_latitude"
                            class="form-control"
                            value="{{ get_settings('hrm_office_latitude') }}"
                            placeholder="e.g. 25.2048">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Office Longitude
                    </label>

                    <input type="number"
                            step="0.0000001"
                            min="-180"
                            max="180"
                            id="officeLongitude"
                            name="hrm_office_longitude"
                            class="form-control"
                            value="{{ get_settings('hrm_office_longitude') }}"
                            placeholder="e.g. 55.2708">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Allowed Radius (meters)
                    </label>

                    <input type="number"
                            step="1"
                            min="10"
                            max="5000"
                            name="hrm_geofence_radius_meters"
                            class="form-control"
                            value="{{ get_settings('hrm_geofence_radius_meters', 200) }}">
                </div>

                <div class="col-md-12 d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" id="useMyLocationBtn" class="btn-nx-outline btn-sm">
                        <i class="ri-crosshair-2-line"></i>
                        Use My Current Location
                    </button>

                    <a href="#" target="_blank" rel="noopener" id="viewOfficeOnMapLink" class="btn-nx-outline btn-sm" style="display:none;">
                        <i class="ri-map-2-line"></i>
                        View on Map
                    </a>

                    <small class="text-muted" id="locationHint">
                        Stand at your office and click "Use My Current Location" — it fills in the coordinates
                        above automatically.
                    </small>
                </div>
            </div>
        </div>

        {{-- Leave Management --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Leave Management</h5>
                    <p>Company-wide defaults for leave year boundaries and carry-forward.</p>
                </div>

                <i class="ri-calendar-check-line"></i>
            </div>

            <div class="row fm-body g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        Leave Year Start Month
                    </label>

                    <select name="hrm_leave_year_start_month" class="form-control select" required>
                        @php
                            $months = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                            ];
                            $currentLeaveYearStart = (int) get_settings('hrm_leave_year_start_month', 1);
                        @endphp

                        @foreach($months as $num => $label)
                            <option value="{{ $num }}" {{ $currentLeaveYearStart === $num ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <small class="text-muted">
                        Leave balances currently reset on the calendar year (January). This is stored ready for a
                        future year-end processing update — changing it does not yet shift the reset date.
                    </small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Default Carry Forward (days)
                    </label>

                    <input type="number"
                            step="0.5"
                            min="0"
                            max="365"
                            name="hrm_leave_default_carry_forward_days"
                            class="form-control"
                            value="{{ get_settings('hrm_leave_default_carry_forward_days') }}"
                            placeholder="e.g., 5">

                    <small class="text-muted">
                        Pre-fills "Max Carry Forward" when creating a new Leave Type. Each leave type can still
                        override this on its own record.
                    </small>
                </div>
            </div>
        </div>

        {{-- Payroll --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Payroll</h5>
                    <p>Controls the pay period window used for unpaid-leave deductions.</p>
                </div>

                <i class="ri-money-dollar-circle-line"></i>
            </div>

            <div class="row fm-body g-3">
                <div class="col-md-12">
                    <label class="form-label">
                        Payroll Cutoff Day
                    </label>

                    <select name="hrm_payroll_cutoff_day" id="payrollCutoffSelect" class="form-control select">
                        @php $currentCutoff = (int) get_settings('hrm_payroll_cutoff_day', 0); @endphp

                        <option value="0" {{ $currentCutoff === 0 ? 'selected' : '' }}>No cutoff (calendar month: 1st – end of month)</option>

                        @for ($day = 1; $day <= 28; $day++)
                            <option value="{{ $day }}" {{ $currentCutoff === $day ? 'selected' : '' }}>
                                {{ $day }}{{ match (true) { $day % 10 === 1 && $day !== 11 => 'st', $day % 10 === 2 && $day !== 12 => 'nd', $day % 10 === 3 && $day !== 13 => 'rd', default => 'th' } }}
                                of the month
                            </option>
                        @endfor
                    </select>

                    <small class="text-muted" id="payrollCutoffHint">
                        Leave on "No cutoff" for a standard 1st-to-end-of-month pay period.
                    </small>
                </div>
            </div>
        </div>

        <div class="settings-save-box mt-3">
            <button type="submit" id="submit" class="settings-submit">
                <i class="ri-save-3-line"></i>
                Update Settings
            </button>
            <button type="button" id="submitting" disabled style="display: none;" class="settings-submit">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            _componentSelect();
            _ajaxFormHandler('.ajax_form');
            _componentRemoteModalLoadAfterAjax();

            $('#weekendDaysSelect').on('change', function () {
                var values = $(this).val() || [];
                $('#weekendDaysHidden').val(values.join(','));
            });

            $('#payrollCutoffSelect').on('change', function () {
                var day = parseInt($(this).val(), 10);

                if (!day) {
                    $('#payrollCutoffHint').text('Leave on "No cutoff" for a standard 1st-to-end-of-month pay period.');
                    return;
                }

                $('#payrollCutoffHint').text(
                    'The pay period will run from the day after the ' + day + ' of the previous month through the ' + day + ' of the payroll month.'
                );
            }).trigger('change');

            // Copy the device token to the clipboard.
            $('.copy-token-btn').on('click', function () {
                var $target = $($(this).data('copy-target'));
                if (!$target.length || !$target.val()) return;

                navigator.clipboard.writeText($target.val()).then(function () {
                    Lobibox.notify('success', {
                        size: 'mini', rounded: true, icon: 'ri-checkbox-circle-line',
                        position: 'bottom right', msg: 'Token copied to clipboard.'
                    });
                });
            });

            // "Use My Current Location" — fills the office lat/long fields
            // from the browser's own geolocation, so the admin doesn't need
            // to know or look up exact coordinates.
            $('#useMyLocationBtn').on('click', function () {
                if (!navigator.geolocation) {
                    $('#locationHint').text('Location services are not available in this browser.');
                    return;
                }

                var $btn = $(this).prop('disabled', true);
                $('#locationHint').text('Getting your current location...');

                navigator.geolocation.getCurrentPosition(function (position) {
                    $('#officeLatitude').val(position.coords.latitude.toFixed(7));
                    $('#officeLongitude').val(position.coords.longitude.toFixed(7));
                    $('#locationHint').text('Location captured. Save the settings to apply it.');
                    $btn.prop('disabled', false);
                    updateOfficeMapLink();
                }, function () {
                    $('#locationHint').text('Could not get your location — please allow location access and try again.');
                    $btn.prop('disabled', false);
                }, { enableHighAccuracy: true, timeout: 15000 });
            });

            function updateOfficeMapLink() {
                var lat = $('#officeLatitude').val();
                var lng = $('#officeLongitude').val();

                if (lat && lng) {
                    $('#viewOfficeOnMapLink').attr('href', 'https://www.google.com/maps?q=' + lat + ',' + lng).show();
                } else {
                    $('#viewOfficeOnMapLink').hide();
                }
            }

            $('#officeLatitude, #officeLongitude').on('input', updateOfficeMapLink);
            updateOfficeMapLink();
        });
    </script>
@endpush
