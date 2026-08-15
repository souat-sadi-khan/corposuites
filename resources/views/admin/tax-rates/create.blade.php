<form class="ajax-form" method="POST" action="{{ route('admin.tax-rates.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Tax Rate</h5>
            <p>Define a tax rate and the ledger accounts it posts to</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Tax Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., VAT 15%, Sales Tax" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Tax Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" placeholder="e.g., VAT15" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Rate (%) <span class="req">*</span></label>
                <input type="number" step="0.0001" min="0" max="100" class="form-control" name="rate" value="0" required>
            </div>
            <div class="fm-field">
                <label>Tax Type <span class="req">*</span></label>
                <select name="tax_type" class="form-select" required>
                    <option value="exclusive">Exclusive (added on top of the price)</option>
                    <option value="inclusive">Inclusive (already within the price)</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Applies To <span class="req">*</span></label>
                <select name="applies_to" class="form-select" required>
                    <option value="both">Both Sales &amp; Purchase</option>
                    <option value="sales">Sales Only</option>
                    <option value="purchase">Purchase Only</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Compound Tax</label>
                <select name="is_compound" class="form-select">
                    <option value="0">No</option>
                    <option value="1">Yes — applied on top of other taxes</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Sales Tax Account (Output Tax)</label>
                <select name="sales_account_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($chartOfAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Purchase Tax Account (Input Tax)</label>
                <select name="purchase_account_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($chartOfAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Effective From</label>
                <input type="date" class="form-control" name="effective_from">
            </div>
            <div class="fm-field">
                <label>Effective To</label>
                <input type="date" class="form-control" name="effective_to">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Leave both dates blank for a rate with no expiry. Tax accounts must be postable (non-group) Chart of Accounts entries.
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
</form>
