<form class="ajax-form" method="POST" action="{{ route('admin.email.sender-identities.test-email', $identity->id) }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Send Test Email</h5>
            <p>Send a test email to your desire location using <b>{{ $identity->name }}</b></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label class="form-label">From Name</label>
                <input type="text" name="name" class="form-control" placeholder="John Doe" value="{{ $identity->name }}" readonly>
            </div>

            <div class="fm-field">
                <label for="email">From Email</label>
                <input type="text" name="email" class="form-control" placeholder="admin@example.com" value="{{ $identity->email }}" readonly>
            </div>

            <div class="fm-field fm-full">
                <label for="to">To Email <span class="text-danger">*</span></label>
                <input type="email" name="to" class="form-control" required placeholder="admin@example.com">
            </div>

            <div class="fm-field fm-full">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required placeholder="Subject">
            </div>

            <div class="fm-field fm-full">
                <label for="form-label">Template/Content</label>
                <textarea name="content" id="content" cols="30" rows="3" class="form-control" required></textarea>
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
            </button>
        </div>
    </div>
</form>
