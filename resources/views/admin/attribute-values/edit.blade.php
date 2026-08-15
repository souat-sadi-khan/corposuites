<form class="ajax-form" method="POST" action="{{ route('admin.attribute-values.update', $attributeValue->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Attribute Value</h5>
            <p>Update value: {{ $attributeValue->value }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Product Attribute <span class="req">*</span></label>
                <select name="product_attribute_id" class="form-select select" required>
                    <option value="">Select Attribute</option>
                    @foreach($productAttributes as $attribute)
                        <option value="{{ $attribute->id }}" {{ old('product_attribute_id', $attributeValue->product_attribute_id) == $attribute->id ? 'selected' : '' }}>{{ $attribute->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Value <span class="req">*</span></label>
                <input type="text" class="form-control" name="value" value="{{ old('value', $attributeValue->value) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $attributeValue->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $attributeValue->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
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
