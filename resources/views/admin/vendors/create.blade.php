<form class="ajax-form vendor-form" method="POST" action="{{ route('admin.vendors.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Vendor</h5>
            <p>Create a new vendor</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Vendor's name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" placeholder="vendor@example.com" autocomplete="off">
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
                <label>Vendor Group</label>
                <select name="vendor_group_id" class="form-select select">
                    <option value="">No Group</option>
                    @foreach($vendorGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
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
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes about this vendor"></textarea>
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
