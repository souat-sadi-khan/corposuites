<form class="ajax-form" method="POST" action="{{ route('admin.email.providers.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Email Provider</h5>
            <p>Configure a new email service provider</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="My SMTP Provider" required>
            </div>

            <div class="fm-field fm-full">
                <label class="form-label">Provider Type <span class="text-danger">*</span></label>
                <select name="type" id="providerType" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="smtp">SMTP</option>
                    <option value="sendgrid">SendGrid</option>
                    <option value="mailgun">Mailgun</option>
                    <option value="ses">Amazon SES</option>
                    <option value="resend">Resend</option>
                    <option value="postmark">Postmark</option>
                    <option value="brevo">Brevo</option>
                    <option value="custom_api">Custom API</option>
                </select>
            </div>

            <!-- Dynamic Configuration Fields -->
            <div class="fm-field fm-full" id="configFieldsContainer">
                <!-- Fields will be injected here by JavaScript -->
            </div>

            <!-- Common fields -->
            <div class="fm-field">
                <label class="form-label">Timeout (seconds)</label>
                <input type="number" name="timeout" class="form-control" placeholder="30" min="1">
            </div>

            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_enabled" class="form-check-input" id="enableSwitch" value="1" checked>
                    <label class="form-check-label" for="enableSwitch">Enabled</label>
                </div>
            </div>

            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_default" class="form-check-input" id="defaultSwitch" value="1">
                    <label class="form-check-label" for="defaultSwitch">Set as Default</label>
                </div>
            </div>

            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="sandbox_mode" class="form-check-input" id="sandboxSwitch" value="1">
                    <label class="form-check-label" for="sandboxSwitch">Sandbox Mode</label>
                </div>
            </div>

            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="maintenance_mode" class="form-check-input" id="maintenanceSwitch" value="1">
                    <label class="form-check-label" for="maintenanceSwitch">Maintenance Mode</label>
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
            </button>
        </div>
    </div>
</form>

<!-- Hidden template for config fields -->
<script id="configFieldTemplate" type="text/template">
    <!-- SMTP fields -->
    <div class="config-fields" data-type="smtp" style="display:none;">
        <div class="fm-grid">
            <div class="fm-field">
                <label class="form-label">Host <span class="text-danger">*</span></label>
                <input type="text" name="config[host]" autocomplete="off" class="form-control" placeholder="smtp.example.com">
            </div>
            <div class="fm-field">
                <label class="form-label">Port <span class="text-danger">*</span></label>
                <input type="number" name="config[port]" class="form-control" placeholder="587">
            </div>
            <div class="fm-field fm-full">
                <label class="form-label">Username</label>
                <input type="text" name="config[username]" autocomplete="off" class="form-control" placeholder="user@example.com">
            </div>
            <div class="fm-field fm-full">
                <label class="form-label">Password</label>
                <input type="password" name="config[password]" autocomplete="off" class="form-control" placeholder="••••••••">
            </div>
            <div class="fm-field">
                <label class="form-label">Encryption</label>
                <select name="config[encryption]" class="form-select">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="">None</option>
                </select>
            </div>
            <div class="fm-field">
                <label class="form-label">Verify Peer</label>
                <select name="config[verify_peer]" class="form-select">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
    </div>

    <!-- API provider fields (generic) -->
    <div class="config-fields" data-type="api" style="display:none;">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label class="form-label">API Key <span class="text-danger">*</span></label>
                <input type="text" autocomplete="off" name="config[api_key]" class="form-control" placeholder="Enter API key">
            </div>
        </div>
    </div>

    <!-- Custom API fields -->
    <div class="config-fields" data-type="custom_api" style="display:none;">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label class="form-label">Endpoint URL <span class="text-danger">*</span></label>
                <input type="url" name="config[endpoint]" class="form-control" placeholder="https://api.example.com/send">
            </div>
            <div class="fm-field fm-full">
                <label class="form-label">API Key <span class="text-danger">*</span></label>
                <input type="password" name="config[api_key]" class="form-control" placeholder="Enter API key">
            </div>
        </div>
    </div>
</script>
