<form class="ajax-form" method="POST" action="{{ route('admin.shifts.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Shift</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Shifts</strong> is used to define employees' working schedules, such as Morning Shift, Evening Shift, or Night Shift. It helps the company manage working hours and accurately track employee attendance according to their assigned schedule.</p>

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
                        <small>The name of the shift, such as Morning Shift, Evening Shift, or Night Shift.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Start Time</strong> <br>
                        <small>The time when the shift officially begins.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>End Time</strong> <br>
                        <small>The time when the shift officially ends.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Description</strong> <br>
                        <small>A short explanation or additional information about the shift.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the shift is currently available for assignment. Options are Active or Inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Create a shift by entering its name, start time, and end time, then optionally add a description and set its status. The shift can then be assigned to employees for attendance and working-hour management. Before deleting a shift, make sure it is not assigned to any employee or required for existing attendance records.</p>

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