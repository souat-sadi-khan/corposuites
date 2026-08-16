<form class="ajax-form" method="POST" action="{{ route('admin.emergency-contacts.update', $emergencyContact->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Emergency Contact</h5>
            <p>Update contact: {{ $emergencyContact->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" data-placeholder="Select Employee" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}" value="{{ $employee->id }}" {{ old('employee_id', $emergencyContact->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $emergencyContact->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Relationship <span class="req">*</span></label>
                <input type="text" class="form-control" name="relationship" value="{{ old('relationship', $emergencyContact->relationship) }}" required>
            </div>
            <div class="fm-field">
                <label>Phone <span class="req">*</span></label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $emergencyContact->phone) }}" required>
            </div>
            <div class="fm-field">
                <label>Alternate Phone</label>
                <input type="text" class="form-control" name="alternate_phone" value="{{ old('alternate_phone', $emergencyContact->alternate_phone) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $emergencyContact->address) }}</textarea>
            </div>
            <div class="fm-field">
                <label>Primary Contact</label>
                <select name="is_primary" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="0" {{ old('is_primary', $emergencyContact->is_primary) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_primary', $emergencyContact->is_primary) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('status', $emergencyContact->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $emergencyContact->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required
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
