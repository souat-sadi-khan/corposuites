<form class="ajax-form" method="POST" action="{{ route('admin.holidays.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Holiday</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Holidays</strong> is used to define official company holidays and non-working days, such as Independence Day, public holidays, or company-specific holidays. It helps HR manage attendance and ensure holidays are properly excluded from regular working days.</p>

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
                        <small>The name of the holiday, such as Independence Day, Victory Day, or Company Foundation Day.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Date</strong> <br>
                        <small>The specific date on which the holiday will be observed.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Description</strong> <br>
                        <small>A short explanation or additional information about the holiday.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the holiday is currently active. Options are Active or Inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Create a holiday by entering its name and date, then optionally add a description and set its status. Active holidays can be considered as non-working days for attendance management. Before deleting a holiday, make sure it is no longer required for attendance records or company holiday planning.</p>
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