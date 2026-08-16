<form class="ajax-form" method="POST" action="{{ route('admin.bank-accounts.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Bank Accounts</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Employee Bank Accounts</strong> is used to maintain employees' bank account information required for salary payments and other company-related financial transactions. It helps HR and Finance keep employee banking details organized and up to date.</p>

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
                        <small>The employee to whom the bank account belongs.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Bank Name</strong> <br>
                        <small>The name of the employee's bank.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Account Holder Name</strong> <br>
                        <small>The name registered on the bank account.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Account Number</strong> <br>
                        <small>The employee's bank account number used for salary or other payments.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>IFSC / SWIFT Code</strong> <br>
                        <small>The bank identification code used for domestic or international banking transactions, where applicable.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Branch Name</strong> <br>
                        <small>The name or location of the bank branch associated with the account.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Primary Account</strong> <br>
                        <small>Determines whether this is the employee's main bank account for salary or company payments.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the bank account is currently active or inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Select the employee and enter the correct bank account details, then mark it as the primary account if it should be used for salary payments. Keep banking information accurate and confidential. Before deleting an account, make sure it is no longer required for payroll or other financial records.</p>
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