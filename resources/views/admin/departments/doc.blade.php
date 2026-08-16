<form class="ajax-form" method="POST" action="{{ route('admin.employee-types.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Department</h5>
            <p>How to use </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Departments</strong> is used to organize employees into different functional areas of the company, such as HR, Finance, Sales, Marketing, IT, and Operations. It helps management clearly identify where each employee works within the organization.</p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Required</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Name</strong> <br>
                        <small>The name of the department, such as Human Resources, Finance, Sales, or IT.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Description</strong> <br>
                        <small>A short explanation of the department and its primary responsibilities.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Create the required departments and assign the appropriate department to each employee. Departments can be updated when the organizational structure changes. Before deleting a department, make sure no employees are assigned to it and that it is not required for existing HR records.</p>

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
