<form class="ajax-form" method="POST" action="{{ route('admin.email.email-templates.update', $template->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Template</h5>
            <p>Update details for "{{ $template->name }}"</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <!-- Name -->
            <div class="fm-field fm-full">
                <label>Name <span class="req">*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $template->name) }}" required>
            </div>

            <!-- Key -->
            <div class="fm-field">
                <label>Key</label>
                <input type="text" class="form-control" name="key" value="{{ old('key', $template->key) }}" autocomplete="off">
                <small class="text-muted">Unique identifier.</small>
            </div>

            <!-- Category -->
            <div class="fm-field">
                <label>Category</label>
                <select name="category" class="form-select select">
                    <option value="">Select Category</option>
                    <option value="welcome" @selected($template->category == 'welcome')>Welcome</option>
                    <option value="notification" @selected($template->category == 'notification')>Notification</option>
                    <option value="newsletter" @selected($template->category == 'newsletter')>Newsletter</option>
                    <option value="password" @selected($template->category == 'password')>Password</option>
                    <option value="verification" @selected($template->category == 'verification')>Verification</option>
                    <option value="onboarding" @selected($template->category == 'onboarding')>Onboarding</option>
                    <option value="other" @selected($template->category == 'other')>Other</option>
                </select>
            </div>

            <!-- Subject -->
            <div class="fm-field fm-full">
                <label>Subject <span class="req">*</span></label>
                <input type="text" class="form-control" name="subject" value="{{ old('subject', $template->subject) }}" required>
            </div>

            <!-- Body -->
            <div class="fm-field fm-full">
                <label>HTML Body <span class="req">*</span></label>
                <textarea class="form-control" name="body" rows="10" required>{{ old('body', $template->body) }}</textarea>
                <small class="text-muted">Use @{{ variable }} placeholders.</small>
            </div>

            <!-- Variables (no JS) -->
            <div class="fm-field fm-full">
                <label>Variables (one per line)</label>
                <textarea class="form-control" name="variables" rows="4">
                    @php
                        $vars = $template->variables;
                        if (is_string($vars)) {
                            $vars = json_decode($vars, true) ?? [];
                        }
                        if (!is_array($vars)) {
                            $vars = [];
                        }
                        echo implode("\n", $vars);
                    @endphp
                </textarea>
                <small class="text-muted">Enter each variable name on a new line.</small>
            </div>

            <!-- Description -->
            <div class="fm-field fm-full">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="2">{{ old('description', $template->description) }}</textarea>
            </div>

            <!-- Sort Order & System flag -->
            <div class="fm-field">
                <label>Sort Order</label>
                <input type="number" class="form-control" name="sort_order" value="{{ old('sort_order', $template->sort_order ?? 0) }}" min="0">
            </div>

            <div class="fm-field">
                <label class="form-label">System Template?</label>
                <div class="form-check form-switch mt-1">
                    <input type="checkbox" class="form-check-input" name="is_system" value="1" id="isSystemEdit" @checked($template->is_system)>
                    <label class="form-check-label" for="isSystemEdit">Mark as system (read‑only)</label>
                </div>
            </div>

            <!-- Status -->
            <div class="fm-field">
                <label class="form-label">Status</label>
                <div class="form-check form-switch mt-1">
                    <input type="checkbox" class="form-check-input" name="status" value="1" id="statusEdit" @checked($template->status)>
                    <label class="form-check-label" for="statusEdit">Active</label>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</form>
