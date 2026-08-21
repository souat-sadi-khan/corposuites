<form class="ajax-form" method="POST" action="{{ route('admin.salary-structures.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Salary Structure</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Salary Structures</strong> define how a specific employee is paid — their pay type (Monthly, Daily, or Commission-based), their base rate, and which Salary Components (allowances/deductions) apply to them. Payroll always uses each employee's latest active salary structure to calculate that period's salary.</p>

        <table class="table table-bordered">
            <thead>
                <tr><th>Field</th><th>Required</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Employee</strong><br><small>The employee this salary structure applies to.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Pay Type</strong><br><small>Determines how the employee is paid — Monthly (fixed), Daily (per day worked), or Commission-based (percentage of sales).</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Basic Salary / Rate</strong><br><small>For Monthly it's the fixed monthly amount. For Daily it's the per-day rate. For Commission-based it's the commission percentage (max 100%). The field label changes automatically based on the selected Pay Type.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Effective Date</strong><br><small>The date this salary structure starts applying from. Payroll always picks the active structure with the most recent effective date.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Salary Components</strong><br><small>Earnings (e.g. House Rent) and deductions (e.g. Tax) added on top of the base rate. Percentage-based components are calculated against the resolved period amount.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
                <tr><td><strong>Status</strong><br><small>Determines whether this structure is currently active. Only one active structure per employee should be in effect at a time (the most recent one wins if there are several).</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
            </tbody>
        </table>

        <p><strong>How each Pay Type works at payroll time:</strong></p>
        <ul>
            <li><strong>Monthly</strong> — the Basic Salary is used as-is for every payroll run.</li>
            <li><strong>Daily</strong> — the Daily Rate is multiplied by the number of days actually worked (from Attendance) within that payroll's pay period.</li>
            <li><strong>Commission-based</strong> — when generating payroll, the admin enters that period's Sales Amount; commission is the Commission Rate (%) applied against it.</li>
        </ul>

        <p>Create a salary structure by selecting the employee, choosing a pay type, setting the rate, and adding any applicable components. Before deactivating or deleting a salary structure, make sure it is not the only active structure for that employee, or payroll can no longer be generated for them.</p>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note"></span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
        </div>
    </div>
</form>
