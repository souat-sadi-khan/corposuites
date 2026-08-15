<form class="ajax-form" method="POST" action="{{ route('admin.opportunities.update', $opportunity->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Opportunity</h5>
            <p>Update opportunity: {{ $opportunity->name }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $opportunity->name) }}" required>
            </div>
            <div class="fm-field">
                <label>Related Lead</label>
                <select name="lead_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}" {{ old('lead_id', $opportunity->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Contact</label>
                <select name="contact_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ old('contact_id', $opportunity->contact_id) == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Related Company</label>
                <select name="company_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $opportunity->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Amount</label>
                <input type="number" step="0.01" min="0" class="form-control" name="amount" value="{{ old('amount', $opportunity->amount) }}">
            </div>
            <div class="fm-field">
                <label>Probability (%)</label>
                <input type="number" min="0" max="100" class="form-control" name="probability" value="{{ old('probability', $opportunity->probability) }}">
            </div>
            <div class="fm-field">
                <label>Stage <span class="req">*</span></label>
                <select name="stage" class="form-select" required>
                    <option value="prospecting" {{ old('stage', $opportunity->stage) == 'prospecting' ? 'selected' : '' }}>Prospecting</option>
                    <option value="qualification" {{ old('stage', $opportunity->stage) == 'qualification' ? 'selected' : '' }}>Qualification</option>
                    <option value="proposal" {{ old('stage', $opportunity->stage) == 'proposal' ? 'selected' : '' }}>Proposal</option>
                    <option value="negotiation" {{ old('stage', $opportunity->stage) == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                    <option value="won" {{ old('stage', $opportunity->stage) == 'won' ? 'selected' : '' }}>Won</option>
                    <option value="lost" {{ old('stage', $opportunity->stage) == 'lost' ? 'selected' : '' }}>Lost</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Expected Close Date</label>
                <input type="date" class="form-control" name="expected_close_date" value="{{ old('expected_close_date', optional($opportunity->expected_close_date)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field fm-full">
                <label>Assigned To</label>
                <select name="assigned_to" class="form-select select">
                    <option value="">Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ old('assigned_to', $opportunity->assigned_to) == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="3">{{ old('notes', $opportunity->notes) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $opportunity->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $opportunity->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Updating...
            </button>
        </div>
    </div>
</form>
