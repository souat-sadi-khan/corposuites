<form class="ajax-form" method="POST" action="{{ route('admin.email.email-templates.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Create Email Template</h5>
            <p>Define a new email template with variables</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <!-- Name -->
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Welcome Email" required>
                <small class="text-muted">A descriptive name for this template.</small>
            </div>

            <!-- Key -->
            <div class="fm-field">
                <label>Key</label>
                <input type="text" class="form-control" name="key" placeholder="welcome_email" autocomplete="off">
                <small class="text-muted">Unique identifier (auto‑generated if left blank).</small>
            </div>

            <!-- Category -->
            <div class="fm-field">
                <label>Category</label>
                <select name="category" data-placeholder="Select Category" class="form-select select">
                    <option value="">Select Category</option>
                    <option value="welcome">Welcome</option>
                    <option value="notification">Notification</option>
                    <option value="newsletter">Newsletter</option>
                    <option value="password">Password</option>
                    <option value="verification">Verification</option>
                    <option value="onboarding">Onboarding</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <!-- Subject -->
            <div class="fm-field fm-full">
                <label>Subject <span class="req">*</span></label>
                <input type="text" class="form-control" name="subject" placeholder="Welcome @{{name}}!" required>
            </div>

            <!-- Body -->
            <div class="fm-field fm-full">
                <label>HTML Body <span class="req">*</span></label>
                <textarea class="form-control" name="body" rows="10" placeholder="<h1>Hello @{{name}}</h1>..." required></textarea>
                <small class="text-muted">Use @{{ variable }} placeholders.</small>
            </div>

            <!-- Variables (no JS) -->
            <div class="fm-field fm-full">
                <label>Variables (one per line)</label>
                <textarea class="form-control" name="variables" rows="4" placeholder="user.name&#10;user.email&#10;login_url"></textarea>
                <small class="text-muted">Enter each variable name on a new line. These will be stored as an array.</small>
            </div>

            <!-- Description -->
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2" placeholder="Brief description of this template"></textarea>
            </div>

            <!-- Sort Order & System flag -->
            <div class="fm-field">
                <label>Sort Order</label>
                <input type="number" class="form-control" name="sort_order" value="0" min="0">
            </div>

            <div class="fm-field">
                <label class="form-label">System Template?</label>
                <div class="form-check form-switch mt-1">
                    <input type="checkbox" class="form-check-input" name="is_system" value="1" id="isSystemCreate">
                    <label class="form-check-label" for="isSystemCreate">Mark as system (read‑only)</label>
                </div>
            </div>

            <!-- Status -->
            <div class="fm-field">
                <label class="form-label">Status</label>
                <div class="form-check form-switch mt-1">
                    <input type="checkbox" class="form-check-input" name="status" value="1" checked id="statusCreate">
                    <label class="form-check-label" for="statusCreate">Active</label>
                </div>
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
