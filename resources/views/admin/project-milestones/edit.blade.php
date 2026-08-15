<form class="ajax-form" method="POST" action="{{ route('admin.project-milestones.update', $projectMilestone->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Milestone</h5>
            <p>
                {{ $projectMilestone->name }} on {{ $projectMilestone->project?->project_code }}
                @if ($projectMilestone->is_overdue) — currently overdue @endif
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Project <span class="req">*</span></label>
                <select name="project_id" class="form-select select" required>
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ $projectMilestone->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Milestone Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $projectMilestone->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Due Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="due_date" value="{{ old('due_date', optional($projectMilestone->due_date)->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Milestone State <span class="req">*</span></label>
                <select name="milestone_status" class="form-select milestone-status-select" required>
                    @foreach (\App\Models\ProjectMilestone::STATUSES as $milestoneStatus)
                        <option value="{{ $milestoneStatus }}" {{ $projectMilestone->milestone_status === $milestoneStatus ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $milestoneStatus)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field milestone-completed-field" style="display:none;">
                <label>Completed Date</label>
                <input type="date" class="form-control" name="completed_date" value="{{ old('completed_date', optional($projectMilestone->completed_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Owner</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $projectMilestone->assigned_to == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Order</label>
                <input type="number" min="1" max="999" class="form-control" name="sort_order" value="{{ old('sort_order', $projectMilestone->sort_order) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectMilestone->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectMilestone->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Deliverables</label>
                <textarea class="form-control" name="deliverables" rows="2">{{ old('deliverables', $projectMilestone->deliverables) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $projectMilestone->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Marking this Completed stamps the completion date; moving it back off Completed clears that date again.
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
