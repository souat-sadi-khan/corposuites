var dataTableInstance;

var DataTableAttendances = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#attendanceTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#attendanceTable').data('url'),
                data: function (d) {
                    d.search = $('#attendanceSearch').val();
                    var employeeId = $('#attendanceTable').data('employee-id');
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

                    // Advanced Search panel — every field is optional; only
                    // the ones actually set get sent, so an untouched panel
                    // behaves exactly like before this feature existed.
                    if ($('#attAdvDateFrom').val()) d.date_from = $('#attAdvDateFrom').val();
                    if ($('#attAdvDateTo').val()) d.date_to = $('#attAdvDateTo').val();
                    if ($('#attAdvDepartment').val()) d.department_id = $('#attAdvDepartment').val();
                    if ($('#attAdvDesignation').val()) d.designation_id = $('#attAdvDesignation').val();
                    if ($('#attAdvShift').val()) d.shift_id = $('#attAdvShift').val();
                    if ($('#attAdvEmployeeType').val()) d.employee_type_id = $('#attAdvEmployeeType').val();
                    if ($('#attAdvEmploymentStatus').val()) d.employment_status_id = $('#attAdvEmploymentStatus').val();
                    if (!employeeId && $('#attAdvEmployee').val()) d.employee_id = $('#attAdvEmployee').val();
                    if ($('#attAdvMissingCheckout').is(':checked')) d.missing_checkout_only = 1;

                    var attStatuses = [];
                    $('.att-adv-status-chk:checked').each(function () {
                        attStatuses.push($(this).val());
                    });
                    if (attStatuses.length) {
                        d.attendance_status = attStatuses.join(',');
                    }
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'employee_name' },
                { data: 'date_formatted' },
                { data: 'timing' },
                { data: 'location', orderable: false, searchable: false },
                { data: 'attendance_status_badge' },
                { data: 'adjustment_badge', orderable: false, searchable: false },
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
                        <img src="${window.location.origin}/assets/images/nothing-to-show.svg" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No attendance records available</p>
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
    DataTableAttendances.init();

    // Search
    $('#attendanceSearch').on('keyup', function () {
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

    // =====================================================
    // Advanced Search panel
    // =====================================================
    $('#attAdvSearchToggle').on('click', function () {
        $('#attAdvPanel').toggleClass('d-none');
    });

    function attAdvActiveCount() {
        var count = 0;
        $('#attAdvDateFrom, #attAdvDateTo, #attAdvDepartment, #attAdvDesignation, #attAdvShift, #attAdvEmployeeType, #attAdvEmploymentStatus, #attAdvEmployee').each(function () {
            if ($(this).val()) count++;
        });
        count += $('.att-adv-status-chk:checked').length;
        if ($('#attAdvMissingCheckout').is(':checked')) count++;
        return count;
    }

    function attAdvUpdateBadge() {
        var count = attAdvActiveCount();
        var $badge = $('#attAdvCount');
        if (count > 0) {
            $badge.text(count).removeClass('d-none');
            $('#attAdvSearchToggle').addClass('is-active');
        } else {
            $badge.addClass('d-none');
            $('#attAdvSearchToggle').removeClass('is-active');
        }
    }

    $('#attAdvApply').on('click', function () {
        attAdvUpdateBadge();
        dataTableInstance.draw();
    });

    $('#attAdvReset').on('click', function () {
        $('#attAdvPanel select').val('').trigger('change'); // select2-aware reset
        $('#attAdvDateFrom, #attAdvDateTo').val('');
        $('.att-adv-status-chk, #attAdvMissingCheckout').prop('checked', false);
        attAdvUpdateBadge();
        dataTableInstance.draw();
    });
});
