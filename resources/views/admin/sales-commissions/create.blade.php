<form class="ajax-form" method="POST" action="{{ route('admin.sales-commissions.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Calculate Sales Commission</h5>
            <p>Commission is calculated from the salesperson's non-cancelled sales orders in the period</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Salesperson <span class="req">*</span></label>
                <select name="admin_id" class="form-select select" required>
                    <option value="">Select Salesperson</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Period Type</label>
                <select name="period_type" class="form-select">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Commission Rate (%) <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" max="100" class="form-control" name="commission_rate" placeholder="e.g., 5.00" required>
            </div>
            <div class="fm-field">
                <label>Period Start <span class="req">*</span></label>
                <input type="date" class="form-control" name="period_start" required>
            </div>
            <div class="fm-field">
                <label>Period End <span class="req">*</span></label>
                <input type="date" class="form-control" name="period_end" required>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Notes about this commission"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. Sales and commission amounts are calculated automatically from confirmed orders in the period.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Calculate
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
