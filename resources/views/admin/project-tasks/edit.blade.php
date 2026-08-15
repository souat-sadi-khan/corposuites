<form class="ajax-form" method="POST" action="{{ route('admin.project-tasks.update', $projectTask->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Task</h5>
            <p>
                {{ $projectTask->task_code }} on {{ $projectTask->project?->project_code }}
                @if ($projectTask->is_overdue) — currently overdue @endif
            </p>
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
                        <option value="{{ $project->id }}" {{ $projectTask->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Milestone</label>
                <select name="project_milestone_id" class="form-select task-milestone-select">
                    <option value="">Not tied to a milestone</option>
                    @foreach ($milestones as $milestone)
                        <option value="{{ $milestone->id }}" data-project-id="{{ $milestone->project_id }}" {{ $projectTask->project_milestone_id == $milestone->id ? 'selected' : '' }}>{{ $milestone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Task Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $projectTask->title) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Assignee</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $projectTask->assigned_to == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    @foreach (\App\Models\ProjectTask::PRIORITIES as $priority)
                        <option value="{{ $priority }}" {{ $projectTask->priority === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Task State <span class="req">*</span></label>
                <select name="task_status" class="form-select task-status-select" required>
                    @foreach (\App\Models\ProjectTask::STATUSES as $taskStatus)
                        <option value="{{ $taskStatus }}" {{ $projectTask->task_status === $taskStatus ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $taskStatus)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field task-progress-field">
                <label>Progress (%)</label>
                <input type="number" min="0" max="100" class="form-control" name="progress_percent" value="{{ old('progress_percent', $projectTask->progress_percent) }}">
            </div>
            <div class="fm-field">
                <label>Start Date</label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', optional($projectTask->start_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Due Date</label>
                <input type="date" class="form-control" name="due_date" value="{{ old('due_date', optional($projectTask->due_date)->toDateString()) }}">
            </div>
            <div class="fm-field task-completed-field" style="display:none;">
                <label>Completed Date</label>
                <input type="date" class="form-control" name="completed_date" value="{{ old('completed_date', optional($projectTask->completed_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Estimated Hours</label>
                <input type="number" step="0.25" min="0" max="9999" class="form-control" name="estimated_hours" value="{{ old('estimated_hours', $projectTask->estimated_hours) }}">
            </div>
            <div class="fm-field">
                <label>Order</label>
                <input type="number" min="1" max="999" class="form-control" name="sort_order" value="{{ old('sort_order', $projectTask->sort_order) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectTask->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectTask->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $projectTask->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $projectTask->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The task code ({{ $projectTask->task_code }}) is fixed. Marking Done sets progress to 100% and stamps the completion date; reopening clears that date.
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
