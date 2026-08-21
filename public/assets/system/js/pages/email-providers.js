var dataTableInstance;
var selectedIds = new Set();

var DataTableEmail = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#emailTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[1, 'asc']], // order by name
            ajax: {
                url: $('#emailTable').data('url'),
                data: function (d) {
                    d.search = $('#emailSearch').val();
                    var statuses = [];

                    $('#tlFilterDd input:checked').each(function () {
                        statuses.push($(this).val());
                    });

                    if (statuses.length) {
                        d.status = statuses.join(',');
                    }
                }
            },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'tl-check-col',
                    render: function (data) {
                        var checked = selectedIds.has(Number(data)) ? 'checked' : '';
                        return `<input type="checkbox" class="tl-row-chk" data-id="${data}" ${checked}>`;
                    }
                },
                {
                    data: 'name',
                    render: function (data, type, row) {
                        return `
                        <div class="tl-name-cell">
                            <button class="tl-expand-btn" title="Expand">
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                            <span class="tl-name-txt">${data}</span>
                        </div>
                        `;
                    }
                },
                {
                    data: 'type_badge'
                },
                {
                    data: 'sender_count',
                    render: function (data) {
                        return data || 0;
                    }
                },
                {
                    data: 'health_status'
                },
                {
                    data: 'is_default',
                    render: function (data) {
                        return data ? '<i class="ri-check-line text-success fs-5"></i>' : '';
                    }
                },
                {
                    data: 'is_enabled',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                }
            ],
            language: {
                emptyTable: `
                <div class="text-center py-4">
                    <img src="${window.location.origin}/assets/images/nothing-to-show.svg" class="img-fluid mb-2" style="max-width:150px">
                    <p class="text-muted mb-0">No email providers found</p>
                </div>
                `
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                syncCurrentPageSelection();
                updateTlInfo();
                _componentSwitch();
                if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
                    _componentRemoteModalLoadAfterAjax();
                }
            }
        });
    };

    return {
        init: function () {
            initDataTable();
            _statusUpdate();
        }
    };
}();

// =====================================================
// Pagination Info
// =====================================================
function updateTlInfo() {
    var info = dataTableInstance.page.info();
    var start = info.recordsDisplay === 0 ? 0 : info.start + 1;

    $('#tlInfo').text(start + ' - ' + info.end + ' of ' + info.recordsDisplay);

    $('#tlPrev').prop('disabled', info.page === 0);
    $('#tlNext').prop('disabled', info.page >= info.pages - 1 || info.pages === 0);
}

// =====================================================
// Selected Checkbox
// =====================================================
function updateSelectedChip() {
    var total = selectedIds.size;
    $('#tlSelectedChip').toggle(total > 0);
    $('#tlSelectedCount').text(total + ' Selected');
    syncCurrentPageSelection();
}

function syncCurrentPageSelection() {
    var currentIds = [];

    dataTableInstance.rows({ page: 'current' }).every(function () {
        currentIds.push(Number(this.data().id));
    });

    $('#emailTable tbody .tl-row-chk').each(function () {
        var id = Number($(this).data('id'));
        var checked = selectedIds.has(id);
        $(this).prop('checked', checked);
        $(this).closest('tr').toggleClass('tl-row-selected', checked);
    });

    var allChecked = currentIds.length > 0 && currentIds.every(function (id) {
        return selectedIds.has(id);
    });

    $('#selectAllChk').prop('checked', allChecked);
}

// =====================================================
// Expand Row Details (optional for providers)
// =====================================================
function renderDetail(row) {
    return `
    <div class="tl-detail">
        <div class="tl-detail-col">
            <h4>Provider Type</h4>
            <div class="tl-detail-row">
                <i class="ri-database-2-line"></i>
                ${row.type || '-'}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>Timeout</h4>
            <div class="tl-detail-row">
                <i class="ri-timer-line"></i>
                ${row.timeout ? row.timeout + 's' : '-'}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>Last Health Check</h4>
            <div class="tl-detail-row">
                <i class="ri-time-line"></i>
                ${row.last_health_check_at ? new Date(row.last_health_check_at).toLocaleString() : '-'}
            </div>
        </div>
    </div>
    `;
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableEmail.init();

    // Search
    $('#emailSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });

    // Next
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    // Row Checkbox
    $('#emailTable tbody').on('change', '.tl-row-chk', function () {
        var id = Number($(this).data('id'));
        if (this.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        $(this).closest('tr').toggleClass('tl-row-selected', this.checked);
        updateSelectedChip();
    });

    // Select All Current Page
    $('#selectAllChk').on('change', function () {
        var checked = this.checked;
        dataTableInstance.rows({ page: 'current' }).every(function () {
            var id = Number(this.data().id);
            if (checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
        updateSelectedChip();
    });

    // Expand Row
    $('#emailTable tbody').on('click', '.tl-expand-btn', function () {
        var btn = $(this);
        var tr = btn.closest('tr');
        var row = dataTableInstance.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('tl-row-expanded');
            btn.removeClass('is-open');
        } else {
            row.child(renderDetail(row.data())).show();
            tr.addClass('tl-row-expanded');
            btn.addClass('is-open');
        }
    });

    // Filter Dropdown
    $('#tlFilterBtn').on('click', function (e) {
        e.stopPropagation();
        $('#tlFilterDd').toggleClass('is-open');
    });

    $('#tlFilterDd').on('click', function (e) {
        e.stopPropagation();
    });

    $(document).on('click', function () {
        $('#tlFilterDd').removeClass('is-open');
    });

    $('#tlFilterDd input').on('change', function () {
        dataTableInstance.draw();
    });

    // =====================================================
    // Custom Actions for Email Providers
    // =====================================================

    // Set Default
    $(document).on('click', '.set-default', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        var url = '/admin/email/providers/' + id + '/default';

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to set this as default?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, set default!'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                method: 'PUT',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',

                success: function (response) {
                    if (response.success) {
                        Lobibox.notify('success', {
                            msg: response.message
                        });

                        if (dataTableInstance) {
                            dataTableInstance.ajax.reload(null, false);
                        }
                    } else {
                        Lobibox.notify('error', {
                            msg: response.message
                        });
                    }
                },

                error: function () {
                    Lobibox.notify('error', {
                        msg: 'Failed to set default.'
                    });
                }
            });
        });
    });

    // Test Connection
    $(document).on('click', '.test-connection', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        var btn = $(this);
        var url = '/admin/email/providers/' + id + '/test-connection';

        Swal.fire({
            title: 'Test Connection?',
            text: "This will attempt to connect to the email provider.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, test it!'
        }).then((result) => {
            if (!result.isConfirmed) return;

            // Disable button and show spinner
            btn.addClass('disabled').find('i').addClass('ri-loader-4-line ri-spin');

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',

                success: function (response) {
                    if (response.success) {
                        Lobibox.notify('success', {
                            msg: response.message
                        });
                    } else {
                        Lobibox.notify('error', {
                            msg: response.message || 'Connection failed.'
                        });
                    }

                    // Refresh DataTable to update health status, if instance exists
                    if (dataTableInstance) {
                        dataTableInstance.ajax.reload(null, false); // or .draw() if you prefer
                    }
                },

                error: function () {
                    Lobibox.notify('error', {
                        msg: 'Test connection request failed.'
                    });
                },

                complete: function () {
                    // Remove spinner and re-enable button
                    btn.removeClass('disabled').find('i').removeClass('ri-loader-4-line ri-spin');
                }
            });
        });
    });

    // Send Test Email – opens modal
    $(document).on('click', '.send-test', function () {
        var providerId = $(this).data('id');
        // Load the test email modal
        $('#globalModal .modal-content').load('/admin/email/providers/' + providerId + '/test-email-modal', function () {
            $('#globalModal').modal('show');
        });
    });

    // Submit Test Email (handled inside modal)
    $(document).on('submit', '#testEmailForm', function (e) {
        e.preventDefault();
        var form = $(this);
        var providerId = form.data('provider-id');
        var submitBtn = form.find('#submit');
        var submittingBtn = form.find('#submitting');

        submitBtn.hide();
        submittingBtn.show();

        $.ajax({
            url: '/admin/email/providers/' + providerId + '/send-test',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#globalModal').modal('hide');
                    dataTableInstance.draw();
                } else {
                    if (response.errors) {
                        $.each(response.errors, function (key, val) {
                            var field = form.find('[name="' + key + '"]');
                            field.addClass('is-invalid');
                            field.siblings('.invalid-feedback').remove();
                            field.after('<div class="invalid-feedback">' + val[0] + '</div>');
                        });
                    }
                    toastr.error(response.message || 'Failed to send test email.');
                }
            },
            error: function () {
                toastr.error('Request failed.');
            },
            complete: function () {
                submitBtn.show();
                submittingBtn.hide();
            }
        });
    });

    // Optional: refresh table after modal close
    $('#globalModal').on('hidden.bs.modal', function () {
        dataTableInstance.draw();
    });
});

// =====================================================
// Dynamic Config Fields
// =====================================================

function getConfigFieldsHtml(type, values) {
    var html = '';
    values = values || {};

    switch (type) {
        case 'smtp':
            html = `
                <div class="fm-grid">
                    <div class="fm-field">
                        <label class="form-label">Host <span class="text-danger">*</span></label>
                        <input type="text" name="config[host]" class="form-control" placeholder="smtp.example.com" value="${values.host || ''}">
                    </div>
                    <div class="fm-field">
                        <label class="form-label">Port <span class="text-danger">*</span></label>
                        <input type="number" name="config[port]" class="form-control" placeholder="587" value="${values.port || ''}">
                    </div>
                    <div class="fm-field fm-full">
                        <label class="form-label">Username</label>
                        <input type="text" name="config[username]" class="form-control" placeholder="user@example.com" value="${values.username || ''}">
                    </div>
                    <div class="fm-field fm-full">
                        <label class="form-label">Password</label>
                        <input type="password" name="config[password]" class="form-control" placeholder="••••••••" value="${values.password || ''}">
                    </div>
                    <div class="fm-field">
                        <label class="form-label">Encryption</label>
                        <select name="config[encryption]" class="form-select">
                            <option value="tls" ${values.encryption === 'tls' ? 'selected' : ''}>TLS</option>
                            <option value="ssl" ${values.encryption === 'ssl' ? 'selected' : ''}>SSL</option>
                            <option value="" ${!values.encryption ? 'selected' : ''}>None</option>
                        </select>
                    </div>
                    <div class="fm-field">
                        <label class="form-label">Verify Peer</label>
                        <select name="config[verify_peer]" class="form-select">
                            <option value="1" ${values.verify_peer == 1 ? 'selected' : ''}>Yes</option>
                            <option value="0" ${values.verify_peer == 0 ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                </div>
            `;
            break;

        case 'sendgrid':
        case 'mailgun':
        case 'ses':
        case 'resend':
        case 'postmark':
        case 'brevo':
            html = `
                <div class="fm-grid">
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="password" name="config[api_key]" class="form-control" placeholder="Enter API key" value="${values.api_key || ''}">
                    </div>
                </div>
            `;
            break;

        case 'custom_api':
            html = `
                <div class="fm-grid">
                    <div class="fm-field fm-full">
                        <label class="form-label">Endpoint URL <span class="text-danger">*</span></label>
                        <input type="url" name="config[endpoint]" class="form-control" placeholder="https://api.example.com/send" value="${values.endpoint || ''}">
                    </div>
                    <div class="fm-field fm-full">
                        <label class="form-label">API Key <span class="text-danger">*</span></label>
                        <input type="password" name="config[api_key]" class="form-control" placeholder="Enter API key" value="${values.api_key || ''}">
                    </div>
                </div>
            `;
            break;

        default:
            html = `<p class="text-muted">Select a provider type to see configuration fields.</p>`;
    }

    return html;
}

function updateConfigFields(containerId, type, values) {
    var container = $('#' + containerId);
    if (!container.length) return;

    var html = getConfigFieldsHtml(type, values);
    container.html(html);
}

// For create modal
$(document).on('change', '#providerType', function () {
    var type = $(this).val();
    updateConfigFields('configFieldsContainer', type, {});
});


// On modal show, if edit mode, pre-fill from server
// We'll add a data attribute on the edit form to pass existing config
// Alternatively, in the edit view, we can output a hidden input with JSON.
// We'll do that by adding:
// <input type="hidden" id="editConfigData" value="{{ json_encode($provider->config) }}">
// And then use that.

// So in edit.blade.php, add:
// <input type="hidden" id="editConfigData" value="{{ json_encode($provider->config) }}">

// Then in JS:
$(document).on('loaded.bs.modal', '#globalModal', function () {
    var configData = $('#editConfigData').val();
    var type = $('#editProviderType').val();
    if (configData && type) {
        try {
            var config = JSON.parse(configData);
            updateConfigFields('editConfigFieldsContainer', type, config);
        } catch (e) {
            updateConfigFields('editConfigFieldsContainer', type, {});
        }
    }
});

$('#editProviderType').off('change').on('change', function () {
    var newType = $(this).val();
    updateConfigFields('editConfigFieldsContainer', newType, {});
});
