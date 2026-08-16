<form class="ajax-form" method="POST" action="{{ route('admin.salary-components.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Salary Component</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Salary Components</strong> are used to define the individual parts of an employee's salary, such as Basic Salary, House Rent Allowance, Bonus, Tax, or Provident Fund. They help HR structure salary calculations by separating earnings and deductions.</p>

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
                        <small>The name of the salary component, such as Basic Salary, House Rent Allowance, Bonus, or Tax.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Code</strong> <br>
                        <small>A unique short code used to identify the salary component, such as BASIC, HRA, or TAX.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Type</strong> <br>
                        <small>Determines whether the component is an Earning added to salary or a Deduction removed from salary.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Calculation Type</strong> <br>
                        <small>Determines whether the component uses a Fixed amount or a Percentage-based calculation.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Value</strong> <br>
                        <small>The fixed amount or percentage value used to calculate the salary component.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Taxable</strong> <br>
                        <small>Determines whether the salary component is included when calculating taxable income.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Description</strong> <br>
                        <small>A short explanation of the salary component and its purpose.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Status</strong> <br>
                        <small>Determines whether the salary component is currently available for use. Options are Active or Inactive.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>Create a salary component by defining its name, code, type, calculation method, and value. Active components can then be included in salary structures and used for payroll calculations. Before deleting a component, make sure it is not being used in existing salary structures or payroll records.</p>
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