<form class="ajax-form" method="POST" action="{{ route('admin.email.providers.update', $provider->id) }}">
    @method('PATCH')

    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Provider: {{ $provider->name }}</h5>
            <p>Update provider configuration</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <!-- Name -->
            <div class="fm-field fm-full">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ $provider->name }}" required>
            </div>

            <!-- Provider Type -->
            <div class="fm-field fm-full">
                <label class="form-label">Provider Type</label>
                <select name="type" id="editProviderType" class="form-select" {{ $provider->senderIdentities()->count() > 0 ? 'disabled' : '' }}>
                    <option value="smtp" @selected($provider->type == 'smtp')>SMTP</option>
                    <option value="sendgrid" @selected($provider->type == 'sendgrid')>SendGrid</option>
                    <option value="mailgun" @selected($provider->type == 'mailgun')>Mailgun</option>
                    <option value="ses" @selected($provider->type == 'ses')>Amazon SES</option>
                    <option value="resend" @selected($provider->type == 'resend')>Resend</option>
                    <option value="postmark" @selected($provider->type == 'postmark')>Postmark</option>
                    <option value="brevo" @selected($provider->type == 'brevo')>Brevo</option>
                    <option value="custom_api" @selected($provider->type == 'custom_api')>Custom API</option>
                </select>
                @if($provider->senderIdentities()->count() > 0)
                    <small class="text-muted">Type cannot be changed because sender identities exist.</small>
                @endif
            </div>

            <!-- ======== CONFIG FIELDS ======== -->
            <div class="fm-field fm-full" id="editConfigFieldsContainer">
                {{-- SMTP --}}
                <div class="config-group" data-type="smtp" style="display:none;">
                    <div class="fm-field mb-3" >
                        <label class="form-label">SMTP Host <span class="text-danger">*</span></label>
                        <input type="text" name="config[host]" class="form-control" value="{{ $provider->config['host'] ?? '' }}">
                    </div>
                    <div class="fm-field mb-3">
                        <label class="form-label">SMTP Port <span class="text-danger">*</span></label>
                        <input type="number" name="config[port]" class="form-control" value="{{ $provider->config['port'] ?? '' }}">
                    </div>
                    <div class="fm-field mb-3 fm-full">
                        <label class="form-label">Username</label>
                        <input type="text" name="config[username]" class="form-control" value="{{ $provider->config['username'] ?? '' }}">
                    </div>
                    <div class="fm-field mb-3 fm-full">
                        <label class="form-label">Password</label>
                        <input type="password" name="config[password]" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                    <div class="fm-field mb-3 fm-full">
                        <label class="form-label">Encryption</label>
                        <select name="config[encryption]" class="form-select">
                            <option value="tls" @selected(($provider->config['encryption'] ?? '') == 'tls')>TLS</option>
                            <option value="ssl" @selected(($provider->config['encryption'] ?? '') == 'ssl')>SSL</option>
                            <option value="none" @selected(($provider->config['encryption'] ?? '') == 'none')>None</option>
                        </select>
                    </div>

                </div>

                {{-- SendGrid --}}
                <div class="config-group" data-type="sendgrid" style="display:none;">
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="text" name="config[api_key]" class="form-control" placeholder="Leave blank to keep current">
                        @if(!empty($provider->config['api_key']))
                            <small class="text-muted"><i class="ri-check-line"></i> Current key is set</small>
                        @endif
                    </div>
                </div>

                {{-- Mailgun --}}
                <div class="config-group" data-type="mailgun" style="display:none;">
                    <div class="fm-field">
                        <label class="form-label">Domain <span class="text-danger">*</span></label>
                        <input type="text" name="config[domain]" class="form-control" value="{{ $provider->config['domain'] ?? '' }}">
                    </div>
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="password" name="config[api_key]" class="form-control" placeholder="Leave blank to keep current">
                        @if(!empty($provider->config['api_key']))
                            <small class="text-muted"><i class="ri-check-line"></i> Current key is set</small>
                        @endif
                    </div>
                </div>

                {{-- SES --}}
                <div class="config-group" data-type="ses" style="display:none;">
                    <div class="fm-field fm-full">
                        <label class="form-label">Access Key ID <span class="text-danger">*</span></label>
                        <input type="text" name="config[key]" class="form-control" value="{{ $provider->config['key'] ?? '' }}">
                    </div>
                    <div class="fm-field fm-full">
                        <label class="form-label">Secret Access Key <span class="text-danger">*</span></label>
                        <input type="password" name="config[secret]" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                    <div class="fm-field fm-full">
                        <label class="form-label">Region <span class="text-danger">*</span></label>
                        <input type="text" name="config[region]" class="form-control" value="{{ $provider->config['region'] ?? '' }}">
                    </div>
                </div>

                {{-- Resend --}}
                <div class="config-group" data-type="resend" style="display:none;">
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="text" name="config[api_key]" class="form-control" placeholder="Leave blank to keep current">
                        @if(!empty($provider->config['api_key']))
                            <small class="text-muted"><i class="ri-check-line"></i> Current key is set</small>
                        @endif
                    </div>
                </div>

                {{-- Postmark --}}
                <div class="config-group" data-type="postmark" style="display:none;">
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="text" name="config[api_key]" class="form-control" placeholder="Leave blank to keep current">
                        @if(!empty($provider->config['api_key']))
                            <small class="text-muted"><i class="ri-check-line"></i> Current key is set</small>
                        @endif
                    </div>
                </div>

                {{-- Brevo --}}
                <div class="config-group" data-type="brevo" style="display:none;">
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="text" name="config[api_key]" class="form-control" placeholder="Leave blank to keep current">
                        @if(!empty($provider->config['api_key']))
                            <small class="text-muted"><i class="ri-check-line"></i> Current key is set</small>
                        @endif
                    </div>
                </div>

                {{-- Custom API --}}
                <div class="config-group" data-type="custom_api" style="display:none;">
                    <div class="fm-field fm-full">
                        <label class="form-label">API Endpoint <span class="text-danger">*</span></label>
                        <input type="url" name="config[endpoint]" class="form-control" value="{{ $provider->config['endpoint'] ?? '' }}">
                    </div>
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="text" name="config[api_key]" class="form-control" placeholder="Leave blank to keep current">
                        @if(!empty($provider->config['api_key']))
                            <small class="text-muted"><i class="ri-check-line"></i> Current key is set</small>
                        @endif
                    </div>
                </div>
            </div>
            <!-- ======== END CONFIG FIELDS ======== -->

            <!-- Common fields -->
            <div class="fm-field">
                <label class="form-label">Timeout (seconds)</label>
                <input type="number" name="timeout" class="form-control" value="{{ $provider->timeout }}" min="1">
            </div>
            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_enabled" class="form-check-input" id="editEnableSwitch" value="1" @checked($provider->is_enabled)>
                    <label class="form-check-label" for="editEnableSwitch">Enabled</label>
                </div>
            </div>
            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_default" class="form-check-input" id="editDefaultSwitch" value="1" @checked($provider->is_default)>
                    <label class="form-check-label" for="editDefaultSwitch">Set as Default</label>
                </div>
            </div>
            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="sandbox_mode" class="form-check-input" id="editSandboxSwitch" value="1" @checked($provider->sandbox_mode)>
                    <label class="form-check-label" for="editSandboxSwitch">Sandbox Mode</label>
                </div>
            </div>
            <div class="fm-field">
                <div class="form-check form-switch">
                    <input type="checkbox" name="maintenance_mode" class="form-check-input" id="editMaintenanceSwitch" value="1" @checked($provider->maintenance_mode)>
                    <label class="form-check-label" for="editMaintenanceSwitch">Maintenance Mode</label>
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
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        // Toggle config groups
        function showConfigGroup(type) {
            $('#editConfigFieldsContainer .config-group')
                .hide()
                .find('input, select')
                .prop('disabled', true);


            var $group = $('#editConfigFieldsContainer .config-group[data-type="' + type + '"]');


            $group
                .show()
                .find('input, select')
                .prop('disabled', false);


            // Required manage
            $('#editConfigFieldsContainer .config-group input, #editConfigFieldsContainer .config-group select')
                .prop('required', false);


            $group.find('input, select').each(function(){

                if($(this).data('required')){
                    $(this).prop('required', true);
                }

            });
        }

        var $typeSelect = $('#editProviderType');
        showConfigGroup($typeSelect.val());

        $typeSelect.on('change', function () {
            showConfigGroup($(this).val());
        });
    });
</script>
