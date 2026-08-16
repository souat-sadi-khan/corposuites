<form class="ajax-form" method="POST" action="{{ route('admin.employee-types.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Types</h5>
            <p>How to use </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Employment Statuses</strong> is used to define an employee’s current employment condition, such as Active, On Leave, Suspended, Resigned, Terminated, or Retired. It helps HR accurately track whether an employee is currently working or no longer active.</p>

        <table>
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
                        <small>The name of the employment status, such as Active, Resigned, or Terminated.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Description</strong> <br>
                        <small>A short explanation of what the employment status represents.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Create the required statuses and assign the appropriate status to each employee based on their current employment condition. Statuses can be updated when an employee’s situation changes. Before deleting a status, make sure it is not assigned to any employee to avoid incorrect or incomplete employee records.</p>

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
