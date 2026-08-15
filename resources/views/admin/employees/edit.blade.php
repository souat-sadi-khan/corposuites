<form class="ajax-form" method="POST" action="{{ route('admin.employees.update', $employee->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Edit Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Photo</label>
                @if($employee->photo)
                    <div class="mb-2">{!! \App\Helpers\Images::show($employee->photo) !!}</div>
                @endif
                <input type="file" class="form-control" name="photo" accept="image/*">
            </div>
            <div class="fm-field">
                <label>Employee Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required>
            </div>
            <div class="fm-field">
                <label>Date of Joining <span class="req">*</span></label>
                <input type="date" class="form-control" name="date_of_joining" value="{{ old('date_of_joining', $employee->date_of_joining?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>First Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required>
            </div>
            <div class="fm-field">
                <label>Last Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required>
            </div>
            <div class="fm-field">
                <label>Email <span class="req">*</span></label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $employee->email) }}" required>
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $employee->phone) }}">
            </div>
            <div class="fm-field">
                <label>Gender</label>
                <select name="gender" class="form-select">
                    <option value="">Select</option>
                    <option value="male" {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender', $employee->gender) == 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Date of Birth</label>
                <input type="date" class="form-control" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Employee Type</label>
                <select name="employee_type_id" class="form-select select">
                    <option value="">Select</option>
                    @foreach($employeeTypes as $type)
                        <option value="{{ $type->id }}" {{ old('employee_type_id', $employee->employee_type_id) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Employment Status</label>
                <select name="employment_status_id" class="form-select select">
                    <option value="">Select</option>
                    @foreach($employmentStatuses as $employmentStatus)
                        <option value="{{ $employmentStatus->id }}" {{ old('employment_status_id', $employee->employment_status_id) == $employmentStatus->id ? 'selected' : '' }}>{{ $employmentStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Shift</label>
                <select name="shift_id" class="form-select select">
                    <option value="">Select</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ old('shift_id', $employee->shift_id) == $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Department</label>
                <select name="department_id" class="form-select select">
                    <option value="">Select</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Designation</label>
                <select name="designation_id" class="form-select select">
                    <option value="">Select</option>
                    @foreach($designations as $designation)
                        <option value="{{ $designation->id }}" {{ old('designation_id', $employee->designation_id) == $designation->id ? 'selected' : '' }}>{{ $designation->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $employee->address) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $employee->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $employee->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">
        <button type="button" class="btn-nx-outline" data-bs-dismiss="offcanvas">
            <i class="ri-close-large-line me-1"></i> Cancel
        </button>

        <button id="submit" type="submit" class="btn-nx-primary">
            <i class="ri-check-line me-1"></i> Update
        </button>
        <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>
