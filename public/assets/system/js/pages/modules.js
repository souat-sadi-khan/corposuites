var dataTableInstance;

var DataTableModules = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#moduleTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#moduleTable').data('url'),
                data: function (d) {
                    d.search = $('#moduleSearch').val();
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
                { data: 'id', visible: false },
                {
                    data: 'name',
                    render: function (data, type, row) {
                        return row.icon_html + ' ' + data;
                    }
                },
                { data: 'slug' },
                { data: 'version', defaultContent: '-' },
                { data: 'status_badge' },
                { data: 'installed', defaultContent: '-' },
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
                        <img src="${window.location.origin}/assets/images/nothing-to-show.png" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No modules available</p>
                    </div>
                `
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateTlInfo();
                _componentSwitch();
                if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
                    _componentRemoteModalLoadAfterAjax();
                }
                _componentSwitch();
                // Re-bind custom button events
                bindActionButtons();
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
// Action Buttons (Toggle Status, Install, Delete)
// =====================================================
function bindActionButtons() {
    // Toggle status (activate/deactivate)
    $('button#toggleStatus').off('click').on('click', function (e) {
        e.preventDefault();
        var btn = $(this);
        var url = btn.data('url');
        var method = btn.data('method') || 'POST';
        $.ajax({
            url: url,
            type: method,
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (response.success || response.status) {
                    dataTableInstance.draw();
                } else {
                    alert('Operation failed: ' + (response.message || 'Unknown error'));
                }
            },
            error: function (xhr) {
                alert('Error: ' + xhr.responseJSON?.message || 'Something went wrong');
            }
        });
    });

    // Install / Uninstall
    $('button#toggleInstall').off('click').on('click', function (e) {
        e.preventDefault();
        var btn = $(this);
        var url = btn.data('url');
        var method = btn.data('method') || 'POST';
        if (!confirm('Are you sure you want to ' + (btn.title || 'perform this action') + '?')) return;
        $.ajax({
            url: url,
            type: method,
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (response.success || response.status) {
                    dataTableInstance.draw();
                } else {
                    alert('Operation failed: ' + (response.message || 'Unknown error'));
                }
            },
            error: function (xhr) {
                alert('Error: ' + xhr.responseJSON?.message || 'Something went wrong');
            }
        });
    });

    // Delete (using existing delete_item handler - but we can override if needed)
    // The delete_item is already handled globally; we keep it.
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableModules.init();

    // Search
    $('#moduleSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous / Next
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    // Filter dropdown
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
});
