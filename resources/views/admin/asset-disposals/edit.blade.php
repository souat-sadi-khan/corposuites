<form class="ajax-form" method="POST" action="{{ route('admin.asset-disposals.update', $disposal->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Disposal</h5>
            <p>
                {{ $disposal->asset->asset_code ?? 'Asset removed' }}
                &middot; Book value {{ number_format($disposal->book_value_at_disposal, 2) }}
                &middot; {{ $disposal->is_gain ? 'Gain' : 'Loss' }} {{ number_format(abs($disposal->gain_loss), 2) }}
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Asset <span class="req">*</span></label>
                <select name="asset_id" class="form-select select" required>
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ (int) $disposal->asset_id === $asset->id ? 'selected' : '' }}>{{ $asset->asset_code }} - {{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Disposal Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="disposal_date" value="{{ old('disposal_date', $disposal->disposal_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="fm-field">
                <label>Disposal Method <span class="req">*</span></label>
                <select name="disposal_method" class="form-select" required>
                    @foreach(['sold' => 'Sold', 'scrapped' => 'Scrapped', 'donated' => 'Donated', 'written_off' => 'Written Off', 'traded_in' => 'Traded In', 'lost' => 'Lost'] as $value => $label)
                        <option value="{{ $value }}" {{ $disposal->disposal_method === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Recipient / Buyer</label>
                <input type="text" class="form-control" name="recipient" value="{{ old('recipient', $disposal->recipient) }}">
            </div>
            <div class="fm-field">
                <label>Proceeds</label>
                <input type="number" step="0.01" min="0" class="form-control" name="proceeds" value="{{ old('proceeds', $disposal->proceeds) }}">
            </div>
            <div class="fm-field">
                <label>Disposal State <span class="req">*</span></label>
                <select name="disposal_status" class="form-select" required>
                    @foreach(['completed' => 'Completed', 'pending' => 'Pending', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $disposal->disposal_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Approved By</label>
                <select name="approved_by" class="form-select select">
                    <option value="">None</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ (int) $disposal->approved_by === $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $disposal->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $disposal->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Reason</label>
                <textarea class="form-control" name="reason" rows="2">{{ old('reason', $disposal->reason) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $disposal->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Changing the asset, date or proceeds re-snapshots the book value and gain/loss. Moving off Completed puts the asset back In Store.
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
