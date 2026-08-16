<form class="ajax-form" method="POST" action="{{ route('admin.employee-types.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Designation</h5>
            <p>How to use </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Designations</strong> is used to define the job titles or positions held by employees, such as Software Engineer, HR Manager, Accountant, Sales Executive, or General Manager. It helps the company organize employees according to their roles and responsibilities.</p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Department</strong> <br>
                        <small>The department associated with the designation. Select a department or choose No Department.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Name</strong> <br>
                        <small>The job title or position name, such as Software Engineer, HR Manager, or Sales Executive.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Description</strong> <br>
                        <small>A short explanation of the designation, role, or responsibilities.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the designation is currently available for use. Options are Active or Inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Create a designation by selecting the relevant department, entering the job title, adding an optional description, and setting its status. Designations can be updated when job roles change. Before deleting a designation, make sure it is not assigned to any employee; otherwise, affected employee records may need to be reassigned to another designation.</p>

    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
        </div>
    </div>
</form>
