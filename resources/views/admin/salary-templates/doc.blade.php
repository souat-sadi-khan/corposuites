<form class="ajax-form" method="POST" action="{{ route('admin.salary-templates.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Salary Templates</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Salary Templates</strong> let you define a pay type, rate, and set of salary components once — then apply it to any number of employees in one action. Useful when a group of employees all share the exact same salary structure (e.g. "10 out of 100 employees are on the same pay").</p>

        <table class="table table-bordered">
            <thead>
                <tr><th>Field</th><th>Required</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Template Name</strong><br><small>A name to identify this template later, e.g. "Retail Staff - Standard" or "Sales Team Commission".</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Pay Type</strong><br><small>Monthly (fixed), Daily (per day worked), or Commission-based (percentage of sales) — same three pay types as Salary Structures.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Basic Salary / Rate</strong><br><small>The amount or rate every employee this template is applied to will receive. The label changes automatically based on the selected Pay Type.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Salary Components</strong><br><small>Earnings and deductions that will be copied onto every employee's salary structure when this template is applied.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
                <tr><td><strong>Description</strong><br><small>An optional note about who this template is meant for.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
                <tr><td><strong>Status</strong><br><small>Determines whether the template is currently available to apply.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
            </tbody>
        </table>

        <p><strong>Applying a template to employees:</strong></p>
        <ul>
            <li>Click the <i class="ri-group-line"></i> <strong>Assign to Employees</strong> icon on any template.</li>
            <li>Select every employee who should receive this exact pay type, rate, and components, along with the effective date.</li>
            <li>On submit, a brand new Salary Structure is created for each selected employee — with the template's pay type, rate, and every component copied over automatically.</li>
        </ul>

        <p>Applying a template never edits an employee's existing salary structure(s) — it always creates a new one, effective from the date you choose, the same way manually adding a Salary Structure does. If the new effective date is the most recent for that employee, Payroll will use it automatically.</p>
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
