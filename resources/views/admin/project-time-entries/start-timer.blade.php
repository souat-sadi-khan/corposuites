<form class="start-timer-form" method="POST" action="{{ route('admin.project-time-entries.start-timer') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Start Timer</h5>
            <p>Starts now — stop it any time from the Time Tracking screen</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Project <span class="req">*</span></label>
                <select name="project_id" class="form-select select ste-project-select" required>
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Task</label>
                <select name="project_task_id" class="form-select ste-task-select">
                    <option value="">Not tied to a specific task</option>
                    @foreach ($tasks as $task)
                        <option value="{{ $task->id }}" data-project-id="{{ $task->project_id }}">{{ $task->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_billable" value="1" id="startTimerBillable" checked>
                    <label class="form-check-label" for="startTimerBillable">Billable time</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Only one timer can run at a time. Stop it before starting another.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-play-circle-line me-1"></i> Start
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Starting...
            </button>
        </div>
    </div>
</form>
