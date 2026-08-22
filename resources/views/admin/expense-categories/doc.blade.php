<form class="ajax-form" method="POST" action="{{ route('admin.expense-categories.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Expense Categories</h5>
            <p>How to use</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Expense Categories</strong> are the dynamic classification an Expense Claim is filed against (e.g. Travel, Meals, Office Supplies), replacing what used to be a hand-typed word. Each category can carry its own spending policy and, optionally, the ledger account it should post to.</p>

        <table class="table table-bordered">
            <thead>
                <tr><th>Field</th><th>Required</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Category Name</strong><br><small>Must be unique — this is what shows up in the Category dropdown on every Expense Claim.</small></td><td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td></tr>
                <tr><td><strong>Max Amount Per Claim</strong><br><small>An optional spending cap. A claim filed against this category for more than this amount is automatically rejected.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
                <tr><td><strong>Receipt Required Above</strong><br><small>An optional threshold. A claim above this amount cannot be saved without an attached receipt (or one already on file).</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
                <tr><td><strong>GL Account</strong><br><small>Which Chart of Accounts entry this category's spend maps to for accounting purposes. Must be a postable (non-group/header) account.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
                <tr><td><strong>Status</strong><br><small>Inactive categories are hidden from the Category dropdown on new/edited Expense Claims, but existing claims keep their category.</small></td><td class="text-center"><i class="ri-close-line text-danger"></i></td></tr>
            </tbody>
        </table>

        <p><strong>Deleting a category</strong> never deletes the claims filed under it — they simply lose their category link and show as "Uncategorised" instead.</p>

        <p>Use the <strong>Advanced Search</strong> to quickly find categories with a spending cap configured, a receipt requirement, or a specific/no GL mapping.</p>
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
