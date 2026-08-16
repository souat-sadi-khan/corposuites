<form class="ajax-form" method="POST" action="{{ route('admin.employee-documents.update', $employeeDocument->id) }}" enctype="multipart/form-data">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Employee Document</h5>
            <p>Update document: {{ $employeeDocument->title }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required data-placeholder="Select Employee">
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}"  value="{{ $employee->id }}" {{ old('employee_id', $employeeDocument->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $employeeDocument->title) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>File</label>
                <div class="mb-2">
                    <a href="{{ asset('storage/' . $employeeDocument->file_path) }}" target="_blank">View current file</a>
                </div>
                <input type="file" class="form-control" name="file">
                <small class="text-muted">Leave blank to keep the existing file.</small>
            </div>
            <div class="fm-field">
                <label>Expiry Date</label>
                <input type="date" class="form-control" name="expiry_date" value="{{ old('expiry_date', $employeeDocument->expiry_date?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('status', $employeeDocument->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $employeeDocument->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $employeeDocument->description) }}</textarea>
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
