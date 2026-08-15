<form class="ajax-form" method="POST" action="{{ route('admin.project-milestones.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Milestone</h5>
            <p>A dated checkpoint the project is delivered against</p>
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
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Milestone Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Phase 1 sign-off" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Due Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="due_date" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="fm-field">
                <label>Milestone State <span class="req">*</span></label>
                <select name="milestone_status" class="form-select milestone-status-select" required>
                    @foreach (\App\Models\ProjectMilestone::STATUSES as $milestoneStatus)
                        <option value="{{ $milestoneStatus }}" {{ $milestoneStatus === 'pending' ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $milestoneStatus)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field milestone-completed-field" style="display:none;">
                <label>Completed Date</label>
                <input type="date" class="form-control" name="completed_date">
            </div>
            <div class="fm-field">
                <label>Owner</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Order</label>
                <input type="number" min="1" max="999" class="form-control" name="sort_order" placeholder="Auto">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Deliverables</label>
                <textarea class="form-control" name="deliverables" rows="2" placeholder="What has to be handed over for this milestone to be met"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Leave Order blank to place the milestone at the end of its project's sequence. Marking it Completed stamps the completion date.
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
