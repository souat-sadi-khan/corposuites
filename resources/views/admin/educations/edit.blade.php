<form class="ajax-form" method="POST" action="{{ route('admin.educations.update', $education->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Education</h5>
            <p>Update record: {{ $education->degree }}</p>
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
                        <option value="{{ $employee->id }}" {{ old('employee_id', $education->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Degree <span class="req">*</span></label>
                <input type="text" class="form-control" name="degree" value="{{ old('degree', $education->degree) }}" required>
            </div>
            <div class="fm-field">
                <label>Institution <span class="req">*</span></label>
                <input type="text" class="form-control" name="institution" value="{{ old('institution', $education->institution) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Field of Study</label>
                <input type="text" class="form-control" name="field_of_study" value="{{ old('field_of_study', $education->field_of_study) }}">
            </div>
            <div class="fm-field">
                <label>Start Year</label>
                <input type="number" class="form-control" name="start_year" min="1950" max="2100" value="{{ old('start_year', $education->start_year) }}">
            </div>
            <div class="fm-field">
                <label>End Year</label>
                <input type="number" class="form-control" name="end_year" min="1950" max="2100" value="{{ old('end_year', $education->end_year) }}">
            </div>
            <div class="fm-field">
                <label>Grade</label>
                <input type="text" class="form-control" name="grade" value="{{ old('grade', $education->grade) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $education->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $education->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $education->description) }}</textarea>
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
