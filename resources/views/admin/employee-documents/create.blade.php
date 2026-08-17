<form class="ajax-form" method="POST" action="{{ route('admin.employee-documents.store') }}" enctype="multipart/form-data">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Employee Document</h5>
            <p>Upload a new document for an employee</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            @if(!Request::has('employee_id'))
                <div class="fm-field fm-full">
                    <label>Employee <span class="req">*</span></label>
                    <select name="employee_id" class="form-select select" data-placeholder="Select Employee" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}" value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                        @endforeach
                    </select>
                </div>
            @else 
                <input type="hidden" name="employee_id" value="{{ Request::get('employee_id') }}">
            @endif 
            
            <div class="fm-field fm-full">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" placeholder="e.g., Passport Copy" required autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>File <span class="req">*</span></label>
                <input type="file" class="form-control" name="file" required>
            </div>
            <div class="fm-field">
                <label>Expiry Date</label>
                <input type="date" class="form-control" name="expiry_date">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the document"></textarea>
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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
