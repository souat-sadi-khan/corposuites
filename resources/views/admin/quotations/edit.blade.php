<form class="ajax-form" method="POST" action="{{ route('admin.quotations.update', $quotation->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Quotation</h5>
            <p>Update quotation: {{ $quotation->quotation_number }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Issue Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="issue_date" value="{{ old('issue_date', optional($quotation->issue_date)->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Valid Until</label>
                <input type="date" class="form-control" name="valid_until" value="{{ old('valid_until', optional($quotation->valid_until)->format('Y-m-d')) }}">
            </div>
            <div class="fm-field">
                <label>Amount <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="amount" value="{{ old('amount', $quotation->amount) }}" required>
            </div>
            <div class="fm-field">
                <label>Quotation Status <span class="req">*</span></label>
                <select name="quotation_status" class="form-select" required>
                    <option value="draft" {{ old('quotation_status', $quotation->quotation_status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ old('quotation_status', $quotation->quotation_status) == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="accepted" {{ old('quotation_status', $quotation->quotation_status) == 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="rejected" {{ old('quotation_status', $quotation->quotation_status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="expired" {{ old('quotation_status', $quotation->quotation_status) == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Related Lead</label>
                <select name="lead_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($leads as $lead)
                        <option value="{{ $lead->id }}" {{ old('lead_id', $quotation->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Contact</label>
                <select name="contact_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ old('contact_id', $quotation->contact_id) == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Company</label>
                <select name="company_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $quotation->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Opportunity</label>
                <select name="opportunity_id" class="form-select select">
                    <option value="">None</option>
                    @foreach($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" {{ old('opportunity_id', $quotation->opportunity_id) == $opportunity->id ? 'selected' : '' }}>{{ $opportunity->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="3">{{ old('notes', $quotation->notes) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Active</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', $quotation->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $quotation->status) == '0' ? 'selected' : '' }}>Inactive</option>
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
