<form class="ajax-form budget-form" method="POST" action="{{ route('admin.budgets.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Create Budget</h5>
            <p>Plan company-wide spend for a period, broken down by ledger account</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Title</label>
                <input type="text" class="form-control" name="title" placeholder="e.g., FY2026 Operating Budget" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Period Type <span class="req">*</span></label>
                <select name="period_type" class="form-select" required>
                    <option value="yearly" selected>Yearly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Period Start <span class="req">*</span></label>
                <input type="date" class="form-control" name="period_start" required>
            </div>
            <div class="fm-field">
                <label>Period End <span class="req">*</span></label>
                <input type="date" class="form-control" name="period_end" required>
            </div>
            <div class="fm-field">
                <label>Budget State <span class="req">*</span></label>
                <select name="budget_status" class="form-select budget-status-select" required>
                    <option value="draft" selected>Draft</option>
                    <option value="approved">Approved</option>
                    <option value="revised">Revised</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="fm-field budget-approval-field" style="display:none;">
                <label>Approved By</label>
                <select name="approved_by" class="form-select select">
                    <option value="">Not recorded</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field budget-approval-field" style="display:none;">
                <label>Approved Date</label>
                <input type="date" class="form-control" name="approved_date">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Budget Lines <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm budget-item-add">
                <i class="ri-add-line"></i> Add Line
            </button>
        </div>
        <div class="budget-item-rows"></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <small class="text-muted">Total Budget</small>
                <div class="fw-bold bud-total-preview">0.00</div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The budget code and version number are issued automatically; the total is summed from the lines on save. Only postable (non-group) accounts can be allocated a planned amount.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>

    {{-- Account options source, consumed client-side by budgets.js. No per-row AJAX. --}}
    <select class="d-none budget-account-options">
        @foreach ($chartOfAccounts as $account)
            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
        @endforeach
    </select>
</form>
