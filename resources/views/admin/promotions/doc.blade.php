<form class="ajax-form" method="POST" action="{{ route('admin.promotions.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Promotions</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Employee Promotions</strong> is used to record an employee's career advancement, such as moving to a higher designation or receiving a salary increase. It helps HR maintain a clear history of employee promotions and track changes in their position and compensation.</p>

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
                        <small>The employee who is receiving the promotion.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>From Designation</strong> <br>
                        <small>The employee's current designation before the promotion.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>To Designation</strong> <br>
                        <small>The new designation assigned to the employee after the promotion.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>From Salary</strong> <br>
                        <small>The employee's salary before the promotion.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>To Salary</strong> <br>
                        <small>The employee's new salary after the promotion.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Promotion Date</strong> <br>
                        <small>The date on which the promotion becomes effective.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the promotion record is currently active or inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Remarks</strong> <br>
                        <small>Additional notes or justification related to the employee's promotion.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Select the employee, enter the previous and new designation and salary information, then set the effective promotion date. Add remarks when additional context is required. Before deleting a promotion record, make sure it is no longer required for employee history, salary records, or HR reporting.</p>
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