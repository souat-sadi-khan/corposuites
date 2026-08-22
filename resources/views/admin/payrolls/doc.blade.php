<form class="ajax-form" method="POST" action="{{ route('admin.payrolls.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Payroll</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Payroll</strong> generates one salary record per employee per month/year, calculated from that employee's currently active Salary Structure — their pay type, base rate, and every Salary Component (earning/deduction) attached to it — plus any Overtime and Attendance-based deductions configured in HRM Settings.</p>

        <table class="table table-bordered">
            <thead>
                <tr><th>Field</th><th>Required</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Employee</strong><br><small>Who this payroll record is for. Only employees with an active Salary Structure can be generated.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Month / Year</strong><br><small>The pay period. Only one payroll record is allowed per employee per period.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Sales Amount</strong><br><small>Only shown/required for employees on a Commission-based structure — that period's sales figure, against which the commission rate is applied.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
                <tr><td><strong>Occurrence Counts</strong><br><small>Only shown for employees who have a per-occurrence Salary Component (e.g. "Late Penalty — $10/occurrence") — how many times it applied this period.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
            </tbody>
        </table>

        <p><strong>Two ways to generate payroll:</strong></p>
        <ul>
            <li><strong>Generate Payroll</strong> — creates one payroll record for a single selected employee.</li>
            <li><strong>Generate for All</strong> — creates payroll for every active employee matching optional filters (Department, Designation, Shift, Employment Status, Employee Type, Gender). An employee is automatically skipped — never aborting the whole batch — if they already have a payroll for that period, have no active salary structure, are commission-based (needs a per-employee sales figure), or have a per-occurrence component (needs a per-employee count); every skip is recorded with a reason in the Activity Log.</li>
        </ul>

        <p><strong>Use the Advanced Search</strong> to narrow the list by Department, Designation, Pay Type, Reimbursement Status, Record Status, Period, or Net Salary range. Once generated, you can <strong>Mark as Paid</strong>, and <strong>Download Payslip</strong> as a professional PDF from each row's actions.</p>
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
