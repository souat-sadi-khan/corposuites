<form class="ajax-form" method="POST" action="{{ route('admin.expense-claims.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Expense Claims</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Expense Claims</strong> let an employee submit an out-of-pocket cost for approval and, once approved, be tracked through to reimbursement.</p>

        <table class="table table-bordered">
            <thead>
                <tr><th>Field</th><th>Required</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Employee</strong><br><small>Who incurred the expense.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Category</strong><br><small>Picked from the dynamic Expense Categories master. Each category can enforce its own maximum amount and receipt requirement.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Amount</strong><br><small>Rejected automatically if it exceeds the selected category's configured maximum.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Expense Date</strong><br><small>The date the cost was actually incurred.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Receipt</strong><br><small>Required if the amount is above the category's configured "Receipt Required Above" threshold.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
            </tbody>
        </table>

        <p><strong>Lifecycle:</strong></p>
        <ul>
            <li><strong>Pending → Approved / Rejected</strong> — use the check/cross icons on each row.</li>
            <li><strong>Mark Reimbursed</strong> — available once a claim is Approved and still Unpaid; records the payment date and method, and cannot be applied twice.</li>
        </ul>

        <p>Use the <strong>Advanced Search</strong> to filter by employee, category, approval state, reimbursement state, receipt presence, expense date range, or amount range — and open the <strong>Expense Claims Report</strong> for spend totals broken down by category and department.</p>
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
