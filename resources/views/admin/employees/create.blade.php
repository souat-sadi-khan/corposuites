<form class="ajax-form" method="POST" action="{{ route('admin.employees.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Add Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <div class="fm-grid fm-body">
            <div class="row fm-full">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6 mb-3 fm-field">
                            <label>Employee Type</label>
                            <select name="employee_type_id" class="form-select select" data-placeholder="Select One" data-minimum-results-for-search="Infinity">
                                <option value="">Select One</option>
                                @foreach($employeeTypes as $type)
                                    <option data-desc="{{ $type->description }}" value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 fm-field">
                            <label>Employment Status</label>
                            <select name="employment_status_id" class="form-select select" data-placeholder="Select One" data-minimum-results-for-search="Infinity">
                                <option value="">Select One</option>
                                @foreach($employmentStatuses as $employmentStatus)
                                    <option data-desc="{{ $employmentStatus->description }}" value="{{ $employmentStatus->id }}">{{ $employmentStatus->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 fm-field">
                            <label>Shift</label>
                            <select name="shift_id" class="form-select select" data-placeholder="Select One" data-minimum-results-for-search="Infinity">
                                <option value="">Select One</option>
                                @foreach($shifts as $shift)
                                    <option data-desc="{{ $shift->description }}" value="{{ $shift->id }}">{{ $shift->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 fm-field">
                            <label>Department</label>
                            <select name="department_id" class="form-select select" data-placeholder="Select One" data-minimum-results-for-search="Infinity">
                                <option value="">Select One</option>
                                @foreach($departments as $department)
                                    <option data-desc="{{ $shift->description }}" value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fm-field">
                            <label>Designation</label>
                            <select name="designation_id" class="form-select select" data-placeholder="Select One" data-minimum-results-for-search="Infinity">
                                <option value="">Select One</option>
                                @foreach($designations as $designation)
                                    <option data-desc="{{ $designation->description }}" value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fm-field fm-full">
                        <label>Avatar</label>
                        <input type="file" class="form-control dropify" name="photo" accept="image/*">
                    </div>
                </div>
            </div>
            
            <div class="fm-field">
                <label>Employee Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="employee_code" placeholder="e.g., EMP-0001" required autocomplete="off">
            </div>

            <div class="fm-field">
                <label>Date of Joining <span class="req">*</span></label>
                <input type="date" class="form-control" name="date_of_joining" required>
            </div>

            <div class="fm-field">
                <label>First Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="first_name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Last Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="last_name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Email <span class="req">*</span></label>
                <input type="email" class="form-control" name="email" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Gender</label>
                <select name="gender" class="form-select select" data-placeholder="Select One" data-minimum-results-for-search="Infinity">
                    <option value="">Select One</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Date of Birth</label>
                <input type="date" class="form-control" name="date_of_birth">
            </div>
            
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-placeholder="Select One" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">
        <button type="button" class="btn-nx-outline" data-bs-dismiss="offcanvas">
            <i class="ri-close-large-line me-1"></i> Cancel
        </button>

        <button id="submit" type="submit" class="btn-nx-primary">
            <i class="ri-check-line me-1"></i> Create
        </button>
        <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>
