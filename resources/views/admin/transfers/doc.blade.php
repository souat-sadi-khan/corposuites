<form class="ajax-form" method="POST" action="{{ route('admin.transfers.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Transfers</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Employee Transfers</strong> is used to record an employee's movement from one department, designation, or organizational position to another. It helps HR maintain a clear history of employee transfers and track changes in their organizational assignment.</p>

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
                        <strong>Employee</strong> <br>
                        <small>The employee who is being transferred.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Current Department</strong> <br>
                        <small>The employee's current department before the transfer.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>To Department</strong> <br>
                        <small>The new department to which the employee will be transferred.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Current Designation</strong> <br>
                        <small>The employee's current job designation before the transfer.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>To Designation</strong> <br>
                        <small>The new designation assigned to the employee after the transfer.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Transfer Date</strong> <br>
                        <small>The date on which the employee's transfer becomes effective.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the transfer record is currently active or inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Reason</strong> <br>
                        <small>The reason or business justification for the employee transfer.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Select the employee, review their current department and designation, then select the new department and designation and set the effective transfer date. Add the reason when required for HR records. Before deleting a transfer, make sure the record is no longer required for employee history, reporting, or organizational records.</p>
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