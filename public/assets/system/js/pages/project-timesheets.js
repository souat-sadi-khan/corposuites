var dataTableInstance;

var DataTableProjectTimesheets = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#timesheetTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#timesheetTable').data('url'),
                data: function (d) {
                    d.search = $('#timesheetSearch').val();
                    d.employee_id = $('#employeeFilter').val();
                    d.timesheet_status = $('#timesheetStatusFilter').val();
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
                { data: 'employee_name' },
                { data: 'week_col' },
                { data: 'hours_col' },
                { data: 'timesheet_status_badge' },
                { data: 'status_badge' },
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
                        <p class="text-muted mb-0">No timesheets available</p>
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
// Regenerate / Submit / Approve — plain confirm + POST + redraw,
// the same shape as sales-commissions.js's mark-paid button.
// =====================================================
function timesheetPost(url, successMessage, extraData) {
    return $.ajax({
        url: url,
        type: 'POST',
        data: extraData || {},
        success: function (res) {
            if (res.status) {
                dataTableInstance.ajax.reload(null, false);
            } else {
                alert(res.message || 'Something went wrong.');
            }
        },
        error: function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong.';
            alert(msg);
        }
    });
}

$(document).on('click', '.timesheet-regenerate-btn', function () {
    timesheetPost($(this).data('url'));
});

$(document).on('click', '.timesheet-submit-btn', function () {
    if (!confirm('Submit this timesheet for approval? Its linked time entries will be locked from further edits.')) {
        return;
    }
    timesheetPost($(this).data('url'));
});

$(document).on('click', '.timesheet-approve-btn', function () {
    if (!confirm('Approve this timesheet?')) {
        return;
    }
    timesheetPost($(this).data('url'));
});

// =====================================================
// Reject — the only action needing a short reason from the
// admin, kept to a plain prompt() rather than a second modal
// for one text field.
// =====================================================
$(document).on('click', '.timesheet-reject-btn', function () {
    var reason = prompt('Reason for rejecting this timesheet:');

    if (reason === null) {
        return;
    }

    if (!reason.trim()) {
        alert('A reason is required to reject a timesheet.');
        return;
    }

    timesheetPost($(this).data('url'), null, { reason: reason.trim() });
});

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectTimesheets.init();

    // Search
    $('#timesheetSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#employeeFilter, #timesheetStatusFilter').on('change', function () {
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
