<form class="ajax-form" method="POST" action="{{ route('admin.performance-reviews.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Performance Review</h5>
            <p>Record a performance review for an employee</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Reviewer</label>
                <select name="reviewer_id" class="form-select select">
                    <option value="">Select Reviewer</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Review Period Start <span class="req">*</span></label>
                <input type="date" class="form-control" name="review_period_start" required>
            </div>
            <div class="fm-field">
                <label>Review Period End <span class="req">*</span></label>
                <input type="date" class="form-control" name="review_period_end" required>
            </div>
            <div class="fm-field">
                <label>Rating (0-5) <span class="req">*</span></label>
                <input type="number" step="0.1" class="form-control" name="rating" min="0" max="5" value="0" required>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Strengths</label>
                <textarea class="form-control" name="strengths" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Areas for Improvement</label>
                <textarea class="form-control" name="areas_for_improvement" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Goals</label>
                <textarea class="form-control" name="goals" rows="2"></textarea>
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
