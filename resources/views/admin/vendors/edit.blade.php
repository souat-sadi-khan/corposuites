<form class="ajax-form vendor-form" method="POST" action="{{ route('admin.vendors.update', $vendor->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Vendor</h5>
            <p>Update vendor: {{ $vendor->name }} <small class="text-muted">({{ $vendor->vendor_code }})</small></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $vendor->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $vendor->email) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $vendor->phone) }}" autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Company Name</label>
                <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $vendor->company_name) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Tax Number</label>
                <input type="text" class="form-control" name="tax_number" value="{{ old('tax_number', $vendor->tax_number) }}">
            </div>
            <div class="fm-field">
                <label>Vendor Group</label>
                <select name="vendor_group_id" class="form-select select">
                    <option value="">No Group</option>
                    @foreach($vendorGroups as $group)
                        <option value="{{ $group->id }}" {{ old('vendor_group_id', $vendor->vendor_group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $vendor->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $vendor->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $vendor->address) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $vendor->notes) }}</textarea>
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
