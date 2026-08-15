<form class="ajax-form" method="POST" action="{{ route('admin.workflow-notification-triggers.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Notification Trigger</h5>
            <p>Configure a notification to fire on a workflow event</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            @if($workflowDefinitionId)
                <input type="hidden" name="workflow_definition_id" value="{{ $workflowDefinitionId }}">
            @else
                <div class="fm-field fm-full">
                    <label>Workflow Definition <span class="req">*</span></label>
                    <select name="workflow_definition_id" class="form-select select" required>
                        <option value="">Select Workflow Definition</option>
                        @foreach($workflowDefinitions as $definition)
                            <option value="{{ $definition->id }}">{{ $definition->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="fm-field">
                <label>Event <span class="req">*</span></label>
                <select name="event" class="form-select" required>
                    <option value="step_pending">Step Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="resubmitted">Resubmitted</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Notify Type <span class="req">*</span></label>
                <select name="notify_type" class="form-select notification-trigger-notify-type" required>
                    <option value="role">Role</option>
                    <option value="user">User</option>
                    <option value="initiator">Initiator</option>
                    <option value="approver">Approver</option>
                </select>
            </div>
            <div class="fm-field notification-trigger-notify-id-wrap" data-for="role">
                <label>Role <span class="req">*</span></label>
                <select name="notify_id" class="form-select select notification-trigger-notify-id">
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Template Message</label>
                <textarea class="form-control" name="template_message" rows="3" placeholder="e.g., Your request has been approved."></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        {{-- Notify target option sources, consumed client-side. --}}
        <select class="d-none notification-trigger-notify-options" data-type="role">
            <option value="">Select Role</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>
        <select class="d-none notification-trigger-notify-options" data-type="user">
            <option value="">Select User</option>
            @foreach($admins as $admin)
                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Fields marked with * are required
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
