<form class="ajax-form" method="POST" action="{{ route('admin.project-tasks.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Task</h5>
            <p>A unit of work on a project, optionally tied to a milestone</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Project <span class="req">*</span></label>
                <select name="project_id" class="form-select select task-project-select" required>
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Milestone</label>
                <select name="project_milestone_id" class="form-select task-milestone-select">
                    <option value="">Not tied to a milestone</option>
                    @foreach ($milestones as $milestone)
                        <option value="{{ $milestone->id }}" data-project-id="{{ $milestone->project_id }}">{{ $milestone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Task Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" placeholder="e.g., Build the login screen" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Assignee</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    @foreach (\App\Models\ProjectTask::PRIORITIES as $priority)
                        <option value="{{ $priority }}" {{ $priority === 'medium' ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Task State <span class="req">*</span></label>
                <select name="task_status" class="form-select task-status-select" required>
                    @foreach (\App\Models\ProjectTask::STATUSES as $taskStatus)
                        <option value="{{ $taskStatus }}" {{ $taskStatus === 'todo' ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $taskStatus)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field task-progress-field">
                <label>Progress (%)</label>
                <input type="number" min="0" max="100" class="form-control" name="progress_percent" value="0">
            </div>
            <div class="fm-field">
                <label>Start Date</label>
                <input type="date" class="form-control" name="start_date">
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date">
            </div>
            <div class="fm-field task-completed-field" style="display:none;">
                <label>Completed Date</label>
                <input type="date" class="form-control" name="completed_date">
            </div>
            <div class="fm-field">
                <label>Estimated Hours</label>
                <input type="number" step="0.25" min="0" max="9999" class="form-control" name="estimated_hours">
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
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The task code is issued automatically. Marking a task Done sets progress to 100% and stamps the completion date. Milestones are limited to the selected project.
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
