<form class="ajax-form" method="POST" action="{{ route('admin.asset-maintenance-records.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Log Maintenance</h5>
            <p>Record maintenance work carried out on an asset</p>
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
                        <option value="{{ $asset->id }}">{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Against Schedule</label>
                <select name="asset_maintenance_schedule_id" class="form-select record-schedule-select">
                    <option value="" data-asset="">Unplanned (no schedule)</option>
                    @foreach($schedules as $schedule)
                        <option value="{{ $schedule->id }}" data-asset="{{ $schedule->asset_id }}">{{ $schedule->title }} — {{ $schedule->asset->asset_code ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" placeholder="e.g., Compressor serviced" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Maintenance Type <span class="req">*</span></label>
                <select name="maintenance_type" class="form-select" required>
                    <option value="preventive">Preventive</option>
                    <option value="inspection">Inspection</option>
                    <option value="calibration">Calibration</option>
                    <option value="servicing">Servicing</option>
                    <option value="repair">Repair</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Performed On <span class="req">*</span></label>
                <input type="date" class="form-control" name="performed_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Work State <span class="req">*</span></label>
                <select name="record_status" class="form-select" required>
                    <option value="completed" selected>Completed</option>
                    <option value="in_progress">In Progress</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Performed By (Technician)</label>
                <select name="performed_by" class="form-select select">
                    <option value="">None</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ trim($employee->first_name . ' ' . $employee->last_name) }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Service Vendor</label>
                <select name="vendor_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Cost</label>
                <input type="number" step="0.01" min="0" class="form-control" name="cost" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Downtime (Hours)</label>
                <input type="number" step="0.01" min="0" class="form-control" name="downtime_hours" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Work Done</label>
                <textarea class="form-control" name="work_done" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Findings</label>
                <textarea class="form-control" name="findings" rows="2" placeholder="Anything noticed during the job"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Logging completed work against a schedule rolls that schedule's next due date forward automatically.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Save
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
