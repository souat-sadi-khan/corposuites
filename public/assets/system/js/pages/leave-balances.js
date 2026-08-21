var dataTableInstance;

var DataTableLeaveBalances = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#leaveBalanceTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#leaveBalanceTable').data('url'),
                data: function (d) {
                    d.search = $('#leaveBalanceSearch').val();
                    var employeeId = $('#leaveBalanceTable').data('employee-id');
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
                { data: 'balance' },
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
                        <p class="text-muted mb-0">No leave balances available</p>
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
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableLeaveBalances.init();

    // Search
    $('#leaveBalanceSearch').on('keyup', function () {
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

    // Generate balances (auto-allocate from policy)
    $('#generateBalances').on('click', function () {
        var url = $(this).data('url');
        var employeeId = $(this).data('employee-id');
        var msg = employeeId
            ? 'Generate leave balances for the selected employee for the current year?'
            : 'Generate leave balances for ALL active employees for the current year?';
        if (!confirm(msg)) return;

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                employee_id: employeeId || null
            },
            success: function (res) {
                notifyBalance(res.status ? 'success' : 'error', res.message || 'Done.');
                if (res.status) dataTableInstance.draw();
            },
            error: function (xhr) {
                notifyBalance('error', xhr.responseJSON?.message || 'Something went wrong');
            }
        });
    });

    // Encash remaining balance
    $(document).on('click', '.encash-balance', function () {
        var url = $(this).data('url');
        var remaining = $(this).data('remaining');
        var input = prompt('Days to encash (max ' + remaining + '). Leave blank to encash all remaining:', '');
        if (input === null) return; // cancelled

        var data = { _token: $('meta[name="csrf-token"]').attr('content') };
        if (input.trim() !== '') data.days = input.trim();

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function (res) {
                notifyBalance(res.status ? 'success' : 'error', res.message || 'Done.');
                if (res.status) dataTableInstance.draw();
            },
            error: function (xhr) {
                notifyBalance('error', xhr.responseJSON?.message || 'Something went wrong');
            }
        });
    });
});

function notifyBalance(type, msg) {
    if (typeof Lobibox !== 'undefined') {
        Lobibox.notify(type, {
            size: 'mini', rounded: true, position: 'bottom right',
            icon: type === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line',
            msg: msg
        });
    } else {
        alert(msg);
    }
}
