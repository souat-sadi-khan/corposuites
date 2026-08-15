<form class="ajax-form" method="POST" action="{{ route('admin.asset-maintenance-schedules.update', $schedule->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Maintenance Schedule</h5>
            <p>
                {{ $schedule->title }}
                @if($schedule->next_due_date)
                    &middot; Next due {{ $schedule->next_due_date->format('d M, Y') }}
                @endif
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Asset <span class="req">*</span></label>
                <select name="asset_id" class="form-select select" required>
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ (int) $schedule->asset_id === $asset->id ? 'selected' : '' }}>{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $schedule->title) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Maintenance Type <span class="req">*</span></label>
                <select name="maintenance_type" class="form-select" required>
                    @foreach(['preventive' => 'Preventive', 'inspection' => 'Inspection', 'calibration' => 'Calibration', 'servicing' => 'Servicing', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" {{ $schedule->maintenance_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Frequency <span class="req">*</span></label>
                <select name="frequency" class="form-select" required>
                    @foreach(['one_time' => 'One Time', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'half_yearly' => 'Half Yearly', 'yearly' => 'Yearly'] as $value => $label)
                        <option value="{{ $value }}" {{ $schedule->frequency === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', $schedule->start_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Last Performed</label>
                <input type="date" class="form-control" name="last_performed_date" value="{{ old('last_performed_date', $schedule->last_performed_date?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Assigned Technician</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">None</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ (int) $schedule->assigned_to === $employee->id ? 'selected' : '' }}>{{ trim($employee->first_name . ' ' . $employee->last_name) }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Service Vendor</label>
                <select name="vendor_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ (int) $schedule->vendor_id === $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Estimated Cost</label>
                <input type="number" step="0.01" min="0" class="form-control" name="estimated_cost" value="{{ old('estimated_cost', $schedule->estimated_cost) }}">
            </div>
            <div class="fm-field">
                <label>Schedule State <span class="req">*</span></label>
                <select name="schedule_status" class="form-select" required>
                    @foreach(['active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $schedule->schedule_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $schedule->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $schedule->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Instructions</label>
                <textarea class="form-control" name="instructions" rows="2">{{ old('instructions', $schedule->instructions) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $schedule->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Updating the last performed date rolls the next due date forward by one interval.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
