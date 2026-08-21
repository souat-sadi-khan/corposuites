var dataTableInstance;

var DataTableLeaveRequests = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#leaveRequestTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#leaveRequestTable').data('url'),
                data: function (d) {
                    d.search = $('#leaveRequestSearch').val();
                    var employeeId = $('#leaveRequestTable').data('employee-id');
                    if (employeeId) {
                        d.employee_id = employeeId;
                    }
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
                { data: 'leave_type_name' },
                { data: 'duration' },
                { data: 'approval_badge' },
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
                        <p class="text-muted mb-0">No leave requests available</p>
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
                bindApprovalButtons();
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
// Approve / Reject
// =====================================================
function bindApprovalButtons() {
    $('button#approveLeaveRequest, button#rejectLeaveRequest').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        var isApprove = this.id === 'approveLeaveRequest';
        if (!confirm('Are you sure you want to perform this action?')) return;
        submitApproval(url, {}, isApprove);
    });

    // Cancel (with optional reason). Refunds balance server-side if approved.
    $('button#cancelLeaveRequest').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        var reason = prompt('Reason for cancellation (optional):', '');
        if (reason === null) return; // dismissed
        submitApproval(url, { cancellation_reason: reason }, false);
    });
}

// Half-day toggle: a half day is a single date, so hide End Date and show the
// session selector. Delegated so it works after the modal loads via AJAX.
$(document).on('change', '#durationType', function () {
    toggleHalfDay($(this).val());
});
$(document).on('shown.bs.modal', function () {
    var $dt = $('#durationType');
    if ($dt.length) toggleHalfDay($dt.val());
});
function toggleHalfDay(value) {
    var isHalf = value === 'half_day';
    $('#halfDaySessionWrap').toggle(isHalf);
    $('#endDateWrap').toggle(!isHalf);
    if (isHalf) {
        // Mirror start date into (hidden) end date so validation passes.
        $('#endDate').val($('#startDate').val());
    }
}
$(document).on('change', '#startDate', function () {
    if ($('#durationType').val() === 'half_day') {
        $('#endDate').val($(this).val());
    }
});

function submitApproval(url, extraData, isApprove) {
    var data = $.extend({ _token: $('meta[name="csrf-token"]').attr('content') }, extraData || {});
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function (response) {
            if (response.status) {
                notifyLeave('success', response.message || 'Done.');
                dataTableInstance.draw();
            } else if (response.requires_override && isApprove) {
                // Warn + allow override: insufficient balance on approval.
                if (confirm(response.message)) {
                    submitApproval(url, { override: 1 }, isApprove);
                }
            } else {
                notifyLeave('error', response.message || 'Operation failed.');
            }
        },
        error: function (xhr) {
            notifyLeave('error', xhr.responseJSON?.message || 'Something went wrong');
        }
    });
}

function notifyLeave(type, msg) {
    if (typeof Lobibox !== 'undefined') {
        Lobibox.notify(type, {
            size: 'mini',
            rounded: true,
            icon: type === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line',
            position: 'bottom right',
            msg: msg
        });
    } else {
        alert(msg);
    }
}

// Surface non-blocking overlap warnings returned by store()/update().
// Uses a global ajaxSuccess hook (immune to the modal handler's .off('submit')).
$(document).ajaxSuccess(function (event, xhr, settings) {
    var url = (settings && settings.url) || '';
    if (url.indexOf('leave-requests') === -1) return;
    if (url.indexOf('/approve') !== -1 || url.indexOf('/reject') !== -1 || url.indexOf('/status/') !== -1) return;
    var res = xhr.responseJSON;
    if (res && res.status && res.warning) {
        notifyLeave('warning', res.warning);
    }
});

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
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableLeaveRequests.init();

    // Search
    $('#leaveRequestSearch').on('keyup', function () {
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
