<form class="ajax-form" method="POST" action="{{ route('admin.asset-assignments.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Assign Asset</h5>
            <p>Hand a registered asset over to an employee</p>
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
                @if($assets->isEmpty())
                    <small class="text-muted">Every available asset is currently assigned or disposed.</small>
                @endif
            </div>
            <div class="fm-field">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select an employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ trim($employee->first_name . ' ' . $employee->last_name) }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assigned Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="assigned_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="fm-field">
                <label>Expected Return Date</label>
                <input type="date" class="form-control" name="expected_return_date" placeholder="Leave blank for open-ended">
            </div>
            <div class="fm-field">
                <label>Condition on Assignment <span class="req">*</span></label>
                <select name="condition_on_assign" class="form-select" required>
                    <option value="new">New</option>
                    <option value="good" selected>Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Assignment State <span class="req">*</span></label>
                <select name="assignment_status" class="form-select assignment-status-select" required>
                    <option value="assigned">Assigned</option>
                    <option value="returned">Returned</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="fm-field assignment-return-field">
                <label>Returned Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="returned_date">
            </div>
            <div class="fm-field assignment-return-field">
                <label>Condition on Return</label>
                <select name="condition_on_return" class="form-select">
                    <option value="">Not recorded</option>
                    <option value="new">New</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
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
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Assigning an asset marks it In Use; returning or cancelling puts it back In Store.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Assign
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
