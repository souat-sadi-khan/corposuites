<form class="ajax-form" method="POST" action="{{ route('admin.asset-maintenance-schedules.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Schedule Maintenance</h5>
            <p>Plan recurring or one-off maintenance for an asset</p>
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
                        <option value="{{ $asset->id }}">{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" placeholder="e.g., Quarterly AC servicing" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Maintenance Type <span class="req">*</span></label>
                <select name="maintenance_type" class="form-select" required>
                    <option value="preventive">Preventive</option>
                    <option value="inspection">Inspection</option>
                    <option value="calibration">Calibration</option>
                    <option value="servicing">Servicing</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Frequency <span class="req">*</span></label>
                <select name="frequency" class="form-select" required>
                    <option value="one_time">One Time</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="half_yearly">Half Yearly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="start_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Last Performed</label>
                <input type="date" class="form-control" name="last_performed_date" placeholder="Leave blank if never">
            </div>
            <div class="fm-field">
                <label>Assigned Technician</label>
                <select name="assigned_to" class="form-select select">
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
                <label>Estimated Cost</label>
                <input type="number" step="0.01" min="0" class="form-control" name="estimated_cost" placeholder="Optional">
            </div>
            <div class="fm-field">
                <label>Schedule State <span class="req">*</span></label>
                <select name="schedule_status" class="form-select" required>
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Instructions</label>
                <textarea class="form-control" name="instructions" rows="2" placeholder="What needs doing"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The next due date is worked out automatically from the frequency and the last performed date.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
