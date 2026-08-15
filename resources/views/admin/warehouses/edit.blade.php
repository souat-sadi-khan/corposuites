<form class="ajax-form warehouse-form" method="POST" action="{{ route('admin.warehouses.update', $warehouse->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Warehouse</h5>
            <p>Update warehouse: {{ $warehouse->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Code <span class="req">*</span></label>
                <input type="text" class="form-control" name="code" value="{{ old('code', $warehouse->code) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $warehouse->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Contact Person</label>
                <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person', $warehouse->contact_person) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone', $warehouse->phone) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $warehouse->email) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $warehouse->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $warehouse->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Address</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $warehouse->address) }}</textarea>
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
