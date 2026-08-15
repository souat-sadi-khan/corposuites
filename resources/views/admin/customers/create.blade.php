<form class="ajax-form customer-form" method="POST" action="{{ route('admin.customers.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Customer</h5>
            <p>Create a new customer</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Customer's full name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" placeholder="customer@example.com" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" placeholder="Phone number" autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Company Name</label>
                <input type="text" class="form-control" name="company_name" placeholder="Company name" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Tax Number</label>
                <input type="text" class="form-control" name="tax_number" placeholder="VAT / Tax ID">
            </div>
            <div class="fm-field">
                <label>Customer Group</label>
                <select name="customer_group_id" class="form-select select">
                    <option value="">No Group</option>
                    @foreach($customerGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Term</label>
                <select name="payment_term_id" class="form-select select">
                    <option value="">No Term</option>
                    @foreach($paymentTerms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <div class="form-check">
                    <input class="form-check-input customer-credit-limit-toggle" type="checkbox" name="credit_limit_enabled" value="1" id="creditLimitEnabled">
                    <label class="form-check-label" for="creditLimitEnabled">Enforce a credit limit for this customer</label>
                </div>
            </div>
            <div class="fm-field fm-full customer-credit-limit-amount" style="display:none;">
                <label>Credit Limit <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="credit_limit" placeholder="e.g., 5000.00">
            </div>
            <div class="fm-field fm-full">
                <label>Billing Address</label>
                <textarea class="form-control" name="billing_address" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Shipping Address</label>
                <textarea class="form-control" name="shipping_address" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes about this customer"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required
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
