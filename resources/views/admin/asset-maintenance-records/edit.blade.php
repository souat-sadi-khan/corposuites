<form class="ajax-form" method="POST" action="{{ route('admin.asset-maintenance-records.update', $record->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Maintenance Record</h5>
            <p>{{ $record->title }} &middot; {{ $record->performed_date?->format('d M, Y') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Asset <span class="req">*</span></label>
                <select name="asset_id" class="form-select select record-asset-select" required>
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ (int) $record->asset_id === $asset->id ? 'selected' : '' }}>{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Against Schedule</label>
                <select name="asset_maintenance_schedule_id" class="form-select record-schedule-select">
                    <option value="" data-asset="">Unplanned (no schedule)</option>
                    @foreach($schedules as $schedule)
                        <option value="{{ $schedule->id }}" data-asset="{{ $schedule->asset_id }}" {{ (int) $record->asset_maintenance_schedule_id === $schedule->id ? 'selected' : '' }}>{{ $schedule->title }} — {{ $schedule->asset->asset_code ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $record->title) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Maintenance Type <span class="req">*</span></label>
                <select name="maintenance_type" class="form-select" required>
                    @foreach(['preventive' => 'Preventive', 'inspection' => 'Inspection', 'calibration' => 'Calibration', 'servicing' => 'Servicing', 'repair' => 'Repair', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" {{ $record->maintenance_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Performed On <span class="req">*</span></label>
                <input type="date" class="form-control" name="performed_date" value="{{ old('performed_date', $record->performed_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Work State <span class="req">*</span></label>
                <select name="record_status" class="form-select" required>
                    @foreach(['completed' => 'Completed', 'in_progress' => 'In Progress', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $record->record_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Performed By (Technician)</label>
                <select name="performed_by" class="form-select select">
                    <option value="">None</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ (int) $record->performed_by === $employee->id ? 'selected' : '' }}>{{ trim($employee->first_name . ' ' . $employee->last_name) }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Service Vendor</label>
                <select name="vendor_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ (int) $record->vendor_id === $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Cost</label>
                <input type="number" step="0.01" min="0" class="form-control" name="cost" value="{{ old('cost', $record->cost) }}">
            </div>
            <div class="fm-field">
                <label>Downtime (Hours)</label>
                <input type="number" step="0.01" min="0" class="form-control" name="downtime_hours" value="{{ old('downtime_hours', $record->downtime_hours) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $record->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $record->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Work Done</label>
                <textarea class="form-control" name="work_done" rows="2">{{ old('work_done', $record->work_done) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Findings</label>
                <textarea class="form-control" name="findings" rows="2">{{ old('findings', $record->findings) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $record->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Changing the date or work state recalculates the linked schedule's next due date.
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
