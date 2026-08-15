<form class="ajax-form" method="POST" action="{{ route('admin.experiences.update', $experience->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Experience</h5>
            <p>Update record: {{ $experience->company_name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $experience->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Company Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $experience->company_name) }}" required>
            </div>
            <div class="fm-field">
                <label>Designation <span class="req">*</span></label>
                <input type="text" class="form-control" name="designation" value="{{ old('designation', $experience->designation) }}" required>
            </div>
            <div class="fm-field">
                <label>Start Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', $experience->start_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>End Date</label>
                <input type="date" class="form-control" name="end_date" value="{{ old('end_date', $experience->end_date?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Currently Working Here</label>
                <select name="is_current" class="form-select">
                    <option value="0" {{ old('is_current', $experience->is_current) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('is_current', $experience->is_current) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $experience->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $experience->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $experience->description) }}</textarea>
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
