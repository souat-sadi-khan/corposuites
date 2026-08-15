<form class="ajax-form" method="POST" action="{{ route('admin.workflow-notification-triggers.update', $workflowNotificationTrigger->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Notification Trigger</h5>
            <p>Update this workflow notification trigger</p>
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
                            <option value="{{ $definition->id }}" {{ old('workflow_definition_id', $workflowNotificationTrigger->workflow_definition_id) == $definition->id ? 'selected' : '' }}>{{ $definition->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="fm-field">
                <label>Event <span class="req">*</span></label>
                <select name="event" class="form-select" required>
                    <option value="step_pending" {{ old('event', $workflowNotificationTrigger->event) == 'step_pending' ? 'selected' : '' }}>Step Pending</option>
                    <option value="approved" {{ old('event', $workflowNotificationTrigger->event) == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ old('event', $workflowNotificationTrigger->event) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="resubmitted" {{ old('event', $workflowNotificationTrigger->event) == 'resubmitted' ? 'selected' : '' }}>Resubmitted</option>
                    <option value="completed" {{ old('event', $workflowNotificationTrigger->event) == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Notify Type <span class="req">*</span></label>
                <select name="notify_type" class="form-select notification-trigger-notify-type" required>
                    <option value="role" {{ old('notify_type', $workflowNotificationTrigger->notify_type) == 'role' ? 'selected' : '' }}>Role</option>
                    <option value="user" {{ old('notify_type', $workflowNotificationTrigger->notify_type) == 'user' ? 'selected' : '' }}>User</option>
                    <option value="initiator" {{ old('notify_type', $workflowNotificationTrigger->notify_type) == 'initiator' ? 'selected' : '' }}>Initiator</option>
                    <option value="approver" {{ old('notify_type', $workflowNotificationTrigger->notify_type) == 'approver' ? 'selected' : '' }}>Approver</option>
                </select>
            </div>
            <div class="fm-field notification-trigger-notify-id-wrap" data-for="{{ old('notify_type', $workflowNotificationTrigger->notify_type) }}">
                <label>Notify Target</label>
                <select name="notify_id" class="form-select select notification-trigger-notify-id">
                    @if($workflowNotificationTrigger->notify_type === 'role')
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('notify_id', $workflowNotificationTrigger->notify_id) == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    @elseif($workflowNotificationTrigger->notify_type === 'user')
                        <option value="">Select User</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ old('notify_id', $workflowNotificationTrigger->notify_id) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Template Message</label>
                <textarea class="form-control" name="template_message" rows="3">{{ old('template_message', $workflowNotificationTrigger->template_message) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $workflowNotificationTrigger->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $workflowNotificationTrigger->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>
</form>
