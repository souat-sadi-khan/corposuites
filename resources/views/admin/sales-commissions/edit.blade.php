<form class="ajax-form" method="POST" action="{{ route('admin.sales-commissions.update', $salesCommission->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Sales Commission</h5>
            <p>Sales and commission amounts will be recalculated on save</p>
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
                        <option value="{{ $admin->id }}" {{ old('admin_id', $salesCommission->admin_id) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Period Type</label>
                <select name="period_type" class="form-select">
                    @foreach(\App\Models\SalesCommission::PERIOD_TYPES as $periodType)
                        <option value="{{ $periodType }}" {{ old('period_type', $salesCommission->period_type) == $periodType ? 'selected' : '' }}>{{ ucfirst($periodType) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Commission Rate (%) <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" max="100" class="form-control" name="commission_rate" value="{{ old('commission_rate', $salesCommission->commission_rate) }}" required>
            </div>
            <div class="fm-field">
                <label>Period Start <span class="req">*</span></label>
                <input type="date" class="form-control" name="period_start" value="{{ old('period_start', optional($salesCommission->period_start)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Period End <span class="req">*</span></label>
                <input type="date" class="form-control" name="period_end" value="{{ old('period_end', optional($salesCommission->period_end)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $salesCommission->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $salesCommission->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $salesCommission->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required.
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
                Updating...
            </button>
        </div>
    </div>
</form>
