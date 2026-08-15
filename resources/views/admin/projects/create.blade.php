<form class="ajax-form" method="POST" action="{{ route('admin.projects.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Create Project</h5>
            <p>Set up a delivery engagement for a client</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Project Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Website Redesign" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Client <span class="req">*</span></label>
                <select name="client_id" class="form-select select" required>
                    <option value="">Select client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->client_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Department</label>
                <select name="department_id" class="form-select select">
                    <option value="">Not assigned</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Project Manager</label>
                <select name="project_manager_id" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Start Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="start_date" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="fm-field">
                <label>Planned End Date</label>
                <input type="date" class="form-control" name="end_date">
            </div>
            <div class="fm-field">
                <label>Priority <span class="req">*</span></label>
                <select name="priority" class="form-select" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Project State <span class="req">*</span></label>
                <select name="project_status" class="form-select project-status-select" required>
                    <option value="planned" selected>Planned</option>
                    <option value="in_progress">In Progress</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="fm-field project-progress-field">
                <label>Progress (%)</label>
                <input type="number" min="0" max="100" class="form-control" name="progress_percent" value="0">
            </div>
            <div class="fm-field project-actual-end-field" style="display:none;">
                <label>Actual End Date</label>
                <input type="date" class="form-control" name="actual_end_date">
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
            <i class="ri-information-line"></i> The project code is issued automatically. Marking a project Completed sets progress to 100% and stamps the actual end date.
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
