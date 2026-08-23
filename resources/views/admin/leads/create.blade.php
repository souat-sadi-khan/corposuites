<form class="ajax-form" method="POST" action="{{ route('admin.leads.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Lead</h5>
            <p>Create a new lead</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Lead's full name" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Email</label>
                <input type="email" class="form-control" name="email" placeholder="lead@example.com" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" placeholder="Phone number" autocomplete="off">
            </div>
            <div class="fm-field fm-full">
                <label>Company Name</label>
                <input type="text" class="form-control" name="company_name" placeholder="Company name" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Lead Source</label>
                <select name="lead_source_id" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="">No Source</option>
                    @foreach($leadSources as $leadSource)
                        <option data-desc="{{ $leadSource->description }}" value="{{ $leadSource->id }}">{{ $leadSource->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Pipeline Stage</label>
                <select name="lead_status_id" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="">No Stage</option>
                    @foreach($leadStatuses as $leadStatus)
                        <option data-desc="{{ $leadStatus->description }}" value="{{ $leadStatus->id }}">{{ $leadStatus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option data-desc="{{ $admin->email }}" value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about this lead"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select select" data-minimum-results-for-search="Infinity">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
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
