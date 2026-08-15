<form class="ajax-form workflow-definition-form" method="POST" action="{{ route('admin.workflow-definitions.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Workflow Definition</h5>
            <p>Build an approval workflow for an HRM module</p>
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
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g., Leave Request Approval" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Approval Mode <span class="req">*</span></label>
                <select name="approval_mode" class="form-select" required>
                    <option value="single">Single</option>
                    <option value="sequential" selected>Sequential</option>
                    <option value="parallel">Parallel</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Based on Template</label>
                <select name="workflow_template_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($workflowTemplates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
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
        <div class="workflow-step-rows"></div>
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
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>

    {{-- Approver option sources, consumed client-side by workflow-definitions.js. No per-row AJAX. --}}
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
