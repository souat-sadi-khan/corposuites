<form class="ajax-form" method="POST" action="{{ route('admin.workflow-statuses.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Workflow Status</h5>
            <p>Create a new custom status for a workflow definition</p>
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
                <label>Key <span class="req">*</span></label>
                <input type="text" class="form-control" name="key" placeholder="e.g., pending" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Label <span class="req">*</span></label>
                <input type="text" class="form-control" name="label" placeholder="e.g., Pending" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Color</label>
                <input type="color" class="form-control form-control-color" name="color" value="#6c757d">
            </div>
            <div class="fm-field">
                <label>Sort Order</label>
                <input type="number" class="form-control" name="sort_order" min="0" value="0">
            </div>
            <div class="fm-field fm-full">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_terminal" value="0">
                    <input type="checkbox" class="form-check-input" id="is_terminal" name="is_terminal" value="1">
                    <label class="form-check-label" for="is_terminal">Terminal Status (ends the workflow)</label>
                </div>
            </div>
        </div>
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
