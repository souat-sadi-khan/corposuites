var dataTableInstance;

var DataTableEmployeeLoans = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#employeeLoanTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#employeeLoanTable').data('url'),
                data: function (d) {
                    d.search = $('#employeeLoanSearch').val();
                    var employeeId = $('#employeeLoanTable').data('employee-id');
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
                { data: 'loan_summary' },
                { data: 'balance' },
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
                        <img src="${window.location.origin}/assets/images/nothing-to-show.svg" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No employee loans available</p>
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
                bindLoanActionButtons();
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
// Approve / Reject / Record Payment
// =====================================================
function bindLoanActionButtons() {
    $('button#approveEmployeeLoan, button#rejectEmployeeLoan').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!confirm('Are you sure you want to perform this action?')) return;
        postLoanAction(url, {});
    });

    $('button#recordLoanPayment').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        var amount = prompt('Enter payment amount:');
        if (!amount || isNaN(amount) || Number(amount) <= 0) return;
        postLoanAction(url, { amount: amount });
    });
}

function postLoanAction(url, data) {
    $.ajax({
        url: url,
        type: 'POST',
        data: Object.assign({ _token: $('meta[name="csrf-token"]').attr('content') }, data),
        success: function (response) {
            if (response.status) {
                dataTableInstance.draw();
            } else {
                alert('Operation failed: ' + (response.message || 'Unknown error'));
            }
        },
        error: function (xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
        }
    });
}

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
    DataTableEmployeeLoans.init();

    // Search
    $('#employeeLoanSearch').on('keyup', function () {
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
