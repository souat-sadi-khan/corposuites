<form class="ajax-form" method="POST" action="{{ route('admin.project-time-entries.update', $projectTimeEntry->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Time Entry</h5>
            <p>{{ $projectTimeEntry->employee?->first_name }} {{ $projectTimeEntry->employee?->last_name }} on {{ $projectTimeEntry->project?->project_code }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $projectTimeEntry->employee_id == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Project <span class="req">*</span></label>
                <select name="project_id" class="form-select select te-project-select" required>
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ $projectTimeEntry->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Task</label>
                <select name="project_task_id" class="form-select te-task-select">
                    <option value="">Not tied to a specific task</option>
                    @foreach ($tasks as $task)
                        <option value="{{ $task->id }}" data-project-id="{{ $task->project_id }}" {{ $projectTimeEntry->project_task_id == $task->id ? 'selected' : '' }}>{{ $task->title }} ({{ $task->task_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Work Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="work_date" value="{{ old('work_date', optional($projectTimeEntry->work_date)->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Hours</label>
                <input type="number" step="0.25" min="0.01" max="24" class="form-control te-hours-input" name="hours" value="{{ old('hours', $projectTimeEntry->hours) }}" placeholder="e.g. 2.5">
                <small class="text-muted">Leave blank if entering start/end times instead</small>
            </div>
            <div class="fm-field">
                <label>Start Time</label>
                <input type="datetime-local" class="form-control te-clock-input" name="started_at" value="{{ old('started_at', optional($projectTimeEntry->started_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="fm-field">
                <label>End Time</label>
                <input type="datetime-local" class="form-control te-clock-input" name="ended_at" value="{{ old('ended_at', optional($projectTimeEntry->ended_at)->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectTimeEntry->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectTimeEntry->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_billable" value="1" id="editBillable" {{ $projectTimeEntry->is_billable ? 'checked' : '' }}>
                    <label class="form-check-label" for="editBillable">Billable time</label>
                </div>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $projectTimeEntry->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Enter either the hours worked, or both a start and end time together — clock times always recalculate the hours automatically. A running timer (no end time yet) can only be stopped from the Time Tracking screen, not edited here.
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
