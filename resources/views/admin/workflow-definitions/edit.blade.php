<form class="ajax-form workflow-definition-form" method="POST" action="{{ route('admin.workflow-definitions.update', $workflowDefinition->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Workflow Definition</h5>
            <p>Update workflow definition: {{ $workflowDefinition->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Module <span class="req">*</span></label>
                <select name="module_key" class="form-select select" required>
                    <option value="">Select Module</option>
                    @foreach($moduleOptions as $key => $label)
                        <option value="{{ $key }}" {{ old('module_key', $workflowDefinition->module_key) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $workflowDefinition->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Approval Mode <span class="req">*</span></label>
                <select name="approval_mode" class="form-select" required>
                    <option value="single" {{ old('approval_mode', $workflowDefinition->approval_mode) == 'single' ? 'selected' : '' }}>Single</option>
                    <option value="sequential" {{ old('approval_mode', $workflowDefinition->approval_mode) == 'sequential' ? 'selected' : '' }}>Sequential</option>
                    <option value="parallel" {{ old('approval_mode', $workflowDefinition->approval_mode) == 'parallel' ? 'selected' : '' }}>Parallel</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Based on Template</label>
                <select name="workflow_template_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($workflowTemplates as $template)
                        <option value="{{ $template->id }}" {{ old('workflow_template_id', $workflowDefinition->workflow_template_id) == $template->id ? 'selected' : '' }}>{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $workflowDefinition->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $workflowDefinition->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Approval Steps <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm workflow-step-add">
                <i class="ri-add-line"></i> Add Step
            </button>
        </div>
        <div class="workflow-step-rows" data-existing="{{ json_encode($workflowDefinition->steps->map(function ($step) {
                return [
                    'name' => $step->name,
                    'approval_type' => $step->approval_type,
                    'approvers' => $step->approvers->map(function ($approver) {
                        return [
                            'approver_type' => $approver->approver_type,
                            'approver_id' => $approver->approver_id,
                        ];
                    })->values(),
                ];
            })->values()) }}"></div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required. Every step needs at least one approver.
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
                Updating...
            </button>
        </div>
    </div>

    <select class="d-none workflow-approver-options" data-type="role">
        <option value="">Select Role</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}">{{ $role->name }}</option>
        @endforeach
    </select>
    <select class="d-none workflow-approver-options" data-type="user">
        <option value="">Select User</option>
        @foreach($admins as $admin)
            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
        @endforeach
    </select>
    <select class="d-none workflow-approver-options" data-type="designation">
        <option value="">Select Designation</option>
        @foreach($designations as $designation)
            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
        @endforeach
    </select>
</form>
