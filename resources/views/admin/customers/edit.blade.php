<form class="ajax-form customer-form" method="POST" action="{{ route('admin.customers.update', $customer->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Customer</h5>
            <p>Update customer: {{ $customer->name }} ({{ $customer->customer_code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $customer->email) }}">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $customer->phone) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Company Name</label>
                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $customer->company_name) }}">
            </div>
            <div class="fm-field">
                <label>Tax Number</label>
                <input type="text" class="form-control" name="tax_number" value="{{ old('tax_number', $customer->tax_number) }}">
            </div>
            <div class="fm-field">
                <label>Customer Group</label>
                <select name="customer_group_id" class="form-select select">
                    <option value="">No Group</option>
                    @foreach($customerGroups as $group)
                        <option value="{{ $group->id }}" {{ old('customer_group_id', $customer->customer_group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Payment Term</label>
                <select name="payment_term_id" class="form-select select">
                    <option value="">No Term</option>
                    @foreach($paymentTerms as $term)
                        <option value="{{ $term->id }}" {{ old('payment_term_id', $customer->payment_term_id) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $customer->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $customer->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <div class="form-check">
                    <input class="form-check-input customer-credit-limit-toggle" type="checkbox" name="credit_limit_enabled" value="1" id="creditLimitEnabled" {{ old('credit_limit_enabled', $customer->credit_limit_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="creditLimitEnabled">Enforce a credit limit for this customer</label>
                </div>
            </div>
            <div class="fm-field fm-full customer-credit-limit-amount" style="display:none;">
                <label>Credit Limit <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" placeholder="e.g., 5000.00">
            </div>
            <div class="fm-field fm-full">
                <label>Billing Address</label>
                <textarea class="form-control" name="billing_address" rows="2">{{ old('billing_address', $customer->billing_address) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Shipping Address</label>
                <textarea class="form-control" name="shipping_address" rows="2">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $customer->notes) }}</textarea>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>
</form>
