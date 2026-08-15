<form class="ajax-form" method="POST" action="{{ route('admin.projects.update', $project->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Project</h5>
            <p>Update: {{ $project->name }} ({{ $project->project_code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Project Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $project->name) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Client <span class="req">*</span></label>
                <select name="client_id" class="form-select select" required>
                    <option value="">Select client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" {{ $project->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }} ({{ $client->client_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Department</label>
                <select name="department_id" class="form-select select">
                    <option value="">Not assigned</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" {{ $project->department_id == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Project Manager</label>
                <select name="project_manager_id" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $project->project_manager_id == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="start_date" value="{{ old('start_date', optional($project->start_date)->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Planned End Date</label>
                <input type="date" class="form-control" name="end_date" value="{{ old('end_date', optional($project->end_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                        <option value="{{ $value }}" {{ $project->priority === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Project State <span class="req">*</span></label>
                <select name="project_status" class="form-select project-status-select" required>
                    @foreach (['planned' => 'Planned', 'in_progress' => 'In Progress', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $project->project_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field project-progress-field">
                <label>Progress (%)</label>
                <input type="number" min="0" max="100" class="form-control" name="progress_percent" value="{{ old('progress_percent', $project->progress_percent) }}">
            </div>
            <div class="fm-field project-actual-end-field" style="display:none;">
                <label>Actual End Date</label>
                <input type="date" class="form-control" name="actual_end_date" value="{{ old('actual_end_date', optional($project->actual_end_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $project->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $project->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $project->description) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $project->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The project code ({{ $project->project_code }}) is fixed. Marking Completed sets progress to 100% and stamps the actual end date; moving it back off Completed clears that date.
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
