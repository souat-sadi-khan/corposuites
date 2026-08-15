<form class="ajax-form" method="POST" action="{{ route('admin.vendor-performance-reviews.update', $vendorPerformanceReview->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Vendor Performance Review</h5>
            <p>Update this review</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Vendor <span class="req">*</span></label>
                <select name="vendor_id" class="form-select select" required>
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ old('vendor_id', $vendorPerformanceReview->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Reviewed By</label>
                <select name="reviewed_by" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('reviewed_by', $vendorPerformanceReview->reviewed_by) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Review Period Start <span class="req">*</span></label>
                <input type="date" class="form-control" name="review_period_start" value="{{ old('review_period_start', optional($vendorPerformanceReview->review_period_start)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Review Period End <span class="req">*</span></label>
                <input type="date" class="form-control" name="review_period_end" value="{{ old('review_period_end', optional($vendorPerformanceReview->review_period_end)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Quality Rating (0-5) <span class="req">*</span></label>
                <input type="number" step="0.1" min="0" max="5" class="form-control" name="quality_rating" value="{{ old('quality_rating', $vendorPerformanceReview->quality_rating) }}" required>
            </div>
            <div class="fm-field">
                <label>Delivery Rating (0-5) <span class="req">*</span></label>
                <input type="number" step="0.1" min="0" max="5" class="form-control" name="delivery_rating" value="{{ old('delivery_rating', $vendorPerformanceReview->delivery_rating) }}" required>
            </div>
            <div class="fm-field">
                <label>Pricing Rating (0-5) <span class="req">*</span></label>
                <input type="number" step="0.1" min="0" max="5" class="form-control" name="pricing_rating" value="{{ old('pricing_rating', $vendorPerformanceReview->pricing_rating) }}" required>
            </div>
            <div class="fm-field">
                <label>Communication Rating (0-5) <span class="req">*</span></label>
                <input type="number" step="0.1" min="0" max="5" class="form-control" name="communication_rating" value="{{ old('communication_rating', $vendorPerformanceReview->communication_rating) }}" required>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $vendorPerformanceReview->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $vendorPerformanceReview->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Remarks</label>
                <textarea class="form-control" name="remarks" rows="2">{{ old('remarks', $vendorPerformanceReview->remarks) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. Overall rating is the average of the four scores, calculated automatically.
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
