<form class="ajax-form" method="POST" action="{{ route('admin.performance-reviews.update', $performanceReview->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Performance Review</h5>
            <p>Update performance review record</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required data-placeholder="Select Employee">
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}"  value="{{ $employee->id }}" {{ old('employee_id', $performanceReview->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Reviewer</label>
                <select name="reviewer_id" class="form-select select">
                    <option value="">Select Reviewer</option>
                    @foreach($employees as $employee)
                        <option data-logo="{{ $employee->photo ? asset($employee->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $employee->email }}"  value="{{ $employee->id }}" {{ old('reviewer_id', $performanceReview->reviewer_id) == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Review Period Start <span class="req">*</span></label>
                <input type="date" class="form-control" name="review_period_start" value="{{ old('review_period_start', $performanceReview->review_period_start?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Review Period End <span class="req">*</span></label>
                <input type="date" class="form-control" name="review_period_end" value="{{ old('review_period_end', $performanceReview->review_period_end?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Rating (0-5) <span class="req">*</span></label>
                <input type="number" step="0.1" class="form-control" name="rating" min="0" max="5" value="{{ old('rating', $performanceReview->rating) }}" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1" {{ old('status', $performanceReview->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $performanceReview->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Strengths</label>
                <textarea class="form-control" name="strengths" rows="2">{{ old('strengths', $performanceReview->strengths) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Areas for Improvement</label>
                <textarea class="form-control" name="areas_for_improvement" rows="2">{{ old('areas_for_improvement', $performanceReview->areas_for_improvement) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Goals</label>
                <textarea class="form-control" name="goals" rows="2">{{ old('goals', $performanceReview->goals) }}</textarea>
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
