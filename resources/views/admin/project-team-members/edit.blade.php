<form class="ajax-form" method="POST" action="{{ route('admin.project-team-members.update', $projectTeamMember->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Team Member</h5>
            <p>
                {{ $projectTeamMember->employee?->first_name }} {{ $projectTeamMember->employee?->last_name }}
                on {{ $projectTeamMember->project?->project_code }} —
                {{ $projectTeamMember->is_current ? 'currently on the team' : 'no longer on the team' }}
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
                        <option value="{{ $project->id }}" {{ $projectTeamMember->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }} ({{ $project->project_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Employee <span class="req">*</span></label>
                <select name="employee_id" class="form-select select" required>
                    <option value="">Select employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $projectTeamMember->employee_id == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Team Role <span class="req">*</span></label>
                <select name="team_role" class="form-select" required>
                    @foreach (\App\Models\ProjectTeamMember::ROLES as $role)
                        <option value="{{ $role }}" {{ $projectTeamMember->team_role === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Allocation (%) <span class="req">*</span></label>
                <input type="number" step="0.01" min="0.01" max="100" class="form-control" name="allocation_percent" value="{{ old('allocation_percent', $projectTeamMember->allocation_percent) }}" required>
            </div>
            <div class="fm-field">
                <label>Joined Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="joined_date" value="{{ old('joined_date', optional($projectTeamMember->joined_date)->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Left Date</label>
                <input type="date" class="form-control" name="left_date" value="{{ old('left_date', optional($projectTeamMember->left_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectTeamMember->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectTeamMember->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $projectTeamMember->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Recording a left date takes the member off the team — which also frees the lead role if they held it.
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
