<form class="ajax-form" method="POST" action="{{ route('admin.asset-assignments.update', $assetAssignment->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Assignment</h5>
            <p>{{ $assetAssignment->asset->asset_code ?? 'Asset removed' }} &middot; {{ ucfirst($assetAssignment->assignment_status) }}</p>
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
                        <option value="{{ $asset->id }}" {{ (int) $assetAssignment->asset_id === $asset->id ? 'selected' : '' }}>{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select an employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ (int) $assetAssignment->employee_id === $employee->id ? 'selected' : '' }}>{{ trim($employee->first_name . ' ' . $employee->last_name) }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assigned Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="assigned_date" value="{{ old('assigned_date', $assetAssignment->assigned_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Expected Return Date</label>
                <input type="date" class="form-control" name="expected_return_date" value="{{ old('expected_return_date', $assetAssignment->expected_return_date?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Condition on Assignment <span class="req">*</span></label>
                <select name="condition_on_assign" class="form-select" required>
                    @foreach(['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $value => $label)
                        <option value="{{ $value }}" {{ $assetAssignment->condition_on_assign === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Assignment State <span class="req">*</span></label>
                <select name="assignment_status" class="form-select assignment-status-select" required>
                    @foreach(['assigned' => 'Assigned', 'returned' => 'Returned', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $assetAssignment->assignment_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field assignment-return-field">
                <label>Returned Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="returned_date" value="{{ old('returned_date', $assetAssignment->returned_date?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field assignment-return-field">
                <label>Condition on Return</label>
                <select name="condition_on_return" class="form-select">
                    <option value="">Not recorded</option>
                    @foreach(['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $value => $label)
                        <option value="{{ $value }}" {{ $assetAssignment->condition_on_return === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $assetAssignment->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $assetAssignment->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $assetAssignment->notes) }}</textarea>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
