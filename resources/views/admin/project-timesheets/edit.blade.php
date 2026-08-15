<form class="ajax-form" method="POST" action="{{ route('admin.project-timesheets.update', $projectTimesheet->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Timesheet</h5>
            <p>{{ $projectTimesheet->employee?->first_name }} {{ $projectTimesheet->employee?->last_name }} &middot; {{ $projectTimesheet->week_label }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Total Hours</label>
                <input type="text" class="form-control" value="{{ number_format($projectTimesheet->total_hours, 2) }}h" disabled>
            </div>
            <div class="fm-field">
                <label>Billable Hours</label>
                <input type="text" class="form-control" value="{{ number_format($projectTimesheet->billable_hours, 2) }}h" disabled>
            </div>
            <div class="fm-field">
                <label>State</label>
                <input type="text" class="form-control" value="{{ $projectTimesheet->timesheet_status_label }}" disabled>
            </div>
            <div class="fm-field">
                <label>Submitted</label>
                <input type="text" class="form-control" value="{{ optional($projectTimesheet->submitted_at)->format('d M Y H:i') ?? '—' }}" disabled>
            </div>
            @if ($projectTimesheet->timesheet_status === 'approved')
                <div class="fm-field fm-full">
                    <label>Approved</label>
                    <input type="text" class="form-control" value="{{ optional($projectTimesheet->approved_at)->format('d M Y H:i') }} by {{ $projectTimesheet->approvedBy?->name }}" disabled>
                </div>
            @endif
            @if ($projectTimesheet->timesheet_status === 'rejected' && $projectTimesheet->rejection_reason)
                <div class="fm-field fm-full">
                    <label>Rejection Reason</label>
                    <textarea class="form-control" rows="2" disabled>{{ $projectTimesheet->rejection_reason }}</textarea>
                </div>
            @endif
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectTimesheet->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectTimesheet->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $projectTimesheet->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Only notes and the archive status can be changed here. Use the Regenerate/Submit/Approve/Reject actions on the list to move this timesheet through its workflow.
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
                Submitting...
            </button>
        </div>
    </div>
</form>
