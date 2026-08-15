<form class="ajax-form" method="POST" action="{{ route('admin.dms.folders.store') }}">
    @csrf
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">New Folder</h5>
            <p>
                @if($parent)
                    Creating inside <strong>{{ $parent->name }}</strong>
                @else
                    Creating in the root folder
                @endif
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <input type="hidden" name="parent_id" value="{{ $parentId }}">

        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Folder Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="e.g. Marketing" required autocomplete="off" autofocus>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note" style="margin:0;">
            <i class="ri-information-line"></i>
            Fields marked with * are required
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
            </button>
        </div>
    </div>
</form>
