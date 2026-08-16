<form class="ajax-form" method="POST" action="{{ route('admin.emergency-contacts.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Emergency Contacts</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Employee Emergency Contacts</strong> is used to maintain the contact details of people who should be contacted in case of an emergency involving an employee. It helps HR quickly reach the employee's family member or other designated emergency contact when needed.</p>

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
                        <small>The employee to whom the emergency contact belongs.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Name</strong> <br>
                        <small>The full name of the emergency contact person.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Relationship</strong> <br>
                        <small>The relationship of the contact person with the employee, such as Father, Mother, Spouse, or Brother.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Phone</strong> <br>
                        <small>The primary phone number used to contact the person during an emergency.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Alternate Phone</strong> <br>
                        <small>An additional phone number that can be used if the primary number is unavailable.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Address</strong> <br>
                        <small>The current address of the emergency contact person.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Primary Contact</strong> <br>
                        <small>Determines whether this person is the employee's primary emergency contact.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the emergency contact is currently active or inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Select the employee, enter the emergency contact's information, and mark the person as the primary contact when appropriate. Contact details should be kept up to date. Before deleting a contact, make sure the employee has another valid emergency contact if required by company policy.</p>
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