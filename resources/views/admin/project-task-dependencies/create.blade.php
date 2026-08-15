<form class="ajax-form" method="POST" action="{{ route('admin.project-task-dependencies.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Dependency</h5>
            <p>Make one task wait on another within the same project</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Project</label>
                <select class="form-select ptd-project-select">
                    <option value="">Select a project to narrow the task lists below</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Predecessor Task <span class="req">*</span></label>
                <select name="depends_on_task_id" class="form-select select ptd-task-select" required>
                    <option value="">Select predecessor task</option>
                    @foreach ($tasks as $task)
                        <option value="{{ $task->id }}" data-project-id="{{ $task->project_id }}">{{ $task->title }} ({{ $task->task_code }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Must finish (or start) first</small>
            </div>
            <div class="fm-field">
                <label>Successor Task <span class="req">*</span></label>
                <select name="task_id" class="form-select select ptd-task-select" required>
                    <option value="">Select successor task</option>
                    @foreach ($tasks as $task)
                        <option value="{{ $task->id }}" data-project-id="{{ $task->project_id }}">{{ $task->title }} ({{ $task->task_code }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Waits on the predecessor</small>
            </div>
            <div class="fm-field">
                <label>Link Type <span class="req">*</span></label>
                <select name="dependency_type" class="form-select" required>
                    @foreach (\App\Models\ProjectTaskDependency::DEPENDENCY_TYPES as $type)
                        <option value="{{ $type }}" {{ $type === 'finish_to_start' ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Lag / Lead (days)</label>
                <input type="number" step="1" min="-365" max="365" class="form-control" name="lag_days" value="0">
                <small class="text-muted">Positive = wait extra days, negative = allow overlap</small>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Both tasks must belong to the same project. A task cannot depend on itself, and a link that would create a circular chain is rejected.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Add
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
