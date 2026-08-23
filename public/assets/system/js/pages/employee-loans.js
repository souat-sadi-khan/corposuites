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

                    applyEmployeeLoanAdvFiltersToRequest(d);
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
            initEmployeeLoanAdvSearch();
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
});

// =====================================================
// Advanced Search — state, select2, chips, apply/reset
// =====================================================

var employeeLoanAdvFilters = {};

function initEmployeeLoanAdvSearch() {

    if (!$('#employeeLoanAdvSearchModal').length) {
        return;
    }

    $('#advEmployee').select2({
        width: '100%',
        dropdownParent: $('#employeeLoanAdvSearchModal'),
        templateResult: _employeeLoanSelectOptionTemplate
    });

    $('.as-select').select2({
        width: '100%',
        dropdownParent: $('#employeeLoanAdvSearchModal')
    });

    $('#advSearchApply').on('click', function () {
        applyEmployeeLoanAdvFilters(true);
    });

    $('#advSearchReset').on('click', function () {
        resetEmployeeLoanAdvFieldsUI();
    });

    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete employeeLoanAdvFilters[key];
        clearEmployeeLoanAdvField(key);
        renderEmployeeLoanFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });

    $(document).on('click', '#advClearAllChips', function () {
        employeeLoanAdvFilters = {};
        resetEmployeeLoanAdvFieldsUI();
        renderEmployeeLoanFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });
}

function _employeeLoanSelectOptionTemplate(option) {
    if (!option.id || !option.element) {
        return option.text;
    }

    var $option = $(option.element);
    var logo = $option.attr('data-logo');
    var desc = $option.attr('data-desc');

    var $opt = $('<div class="sel-opt-rich"></div>');

    if (logo) {
        $opt.append(
            '<div class="sel-opt-rich-avatar">' +
                '<img class="sel-opt-rich-img" src="' + logo + '" alt="">' +
            '</div>'
        );
    }

    var $info = $('<div class="sel-opt-rich-info"></div>');
    $info.append($('<div class="sel-opt-rich-name"></div>').text(option.text));

    if (desc) {
        $info.append($('<div class="sel-opt-rich-desc"></div>').text(desc));
    }

    $opt.append($info);

    return $opt;
}

function clearEmployeeLoanAdvField(key) {
    switch (key) {
        case 'employee_id':
            $('#advEmployee').val('').trigger('change.select2');
            break;
        case 'approval_status':
            $('#advApprovalStatus').val('').trigger('change.select2');
            break;
        case 'status':
            $('#advRecordStatus').val('').trigger('change.select2');
            break;
        case 'payment_state':
            $('#advPaymentState').val('').trigger('change.select2');
            break;
        case 'deduct_from_salary':
            $('#advDeductFromSalary').val('').trigger('change.select2');
            break;
        case 'loan_amount_range':
            $('#advLoanAmountMin, #advLoanAmountMax').val('');
            break;
        case 'start_date':
            $('#advStartDateFrom, #advStartDateTo').val('');
            break;
    }
}

function resetEmployeeLoanAdvFieldsUI() {
    $('#advEmployee, #advApprovalStatus, #advRecordStatus, #advPaymentState, #advDeductFromSalary')
        .val('')
        .trigger('change.select2');

    $('#advLoanAmountMin, #advLoanAmountMax, #advStartDateFrom, #advStartDateTo').val('');
}

function collectEmployeeLoanAdvFilters() {

    var filters = {};

    var $employee = $('#advEmployee');
    if ($employee.val()) {
        filters.employee_id = {
            value: $employee.val(),
            label: 'Employee: ' + $employee.find('option:selected').text()
        };
    }

    var $approvalStatus = $('#advApprovalStatus');
    if ($approvalStatus.val()) {
        filters.approval_status = {
            value: $approvalStatus.val(),
            label: 'Approval: ' + $approvalStatus.find('option:selected').text()
        };
    }

    var $recordStatus = $('#advRecordStatus');
    if ($recordStatus.val() !== '') {
        filters.status = {
            value: $recordStatus.val(),
            label: 'Record Status: ' + $recordStatus.find('option:selected').text()
        };
    }

    var $paymentState = $('#advPaymentState');
    if ($paymentState.val()) {
        filters.payment_state = {
            value: $paymentState.val(),
            label: 'Payment: ' + $paymentState.find('option:selected').text()
        };
    }

    var $deductFromSalary = $('#advDeductFromSalary');
    if ($deductFromSalary.val() !== '') {
        filters.deduct_from_salary = {
            value: $deductFromSalary.val(),
            label: 'Salary Deduction: ' + $deductFromSalary.find('option:selected').text()
        };
    }

    var loanMin = $('#advLoanAmountMin').val();
    var loanMax = $('#advLoanAmountMax').val();
    if (loanMin !== '' || loanMax !== '') {
        filters.loan_amount_range = {
            value: { min: loanMin, max: loanMax },
            label: 'Loan Amount: ' + (loanMin !== '' ? loanMin : '0') + ' - ' + (loanMax !== '' ? loanMax : '∞')
        };
    }

    var startFrom = $('#advStartDateFrom').val();
    var startTo = $('#advStartDateTo').val();
    if (startFrom || startTo) {
        filters.start_date = {
            value: { from: startFrom, to: startTo },
            label: 'Start: ' + (startFrom || '…') + ' → ' + (startTo || '…')
        };
    }

    return filters;
}

function renderEmployeeLoanFilterChips() {

    var $bar = $('#advSearchChipsBar');
    var $chips = $('#advSearchChips');
    var keys = Object.keys(employeeLoanAdvFilters);

    $chips.empty();

    if (!keys.length) {
        $bar.hide();
        $('#advSearchBadge').hide();
        return;
    }

    keys.forEach(function (key) {

        var filter = employeeLoanAdvFilters[key];

        var $remove = $('<button type="button" class="adv-chip-remove"></button>')
            .attr('data-key', key)
            .html('<i class="ri-close-line"></i>');

        var $chip = $('<span class="adv-chip"></span>')
            .attr('data-key', key)
            .append($('<span></span>').text(filter.label))
            .append($remove);

        $chips.append($chip);
    });

    $chips.append(
        $('<button type="button" class="adv-chip-clear-all" id="advClearAllChips"></button>')
            .html('<i class="ri-close-circle-line"></i> Clear all')
    );

    $bar.show();

    $('#advSearchBadge').text(keys.length).show();
}

function applyEmployeeLoanAdvFilters(closeModal) {

    employeeLoanAdvFilters = collectEmployeeLoanAdvFilters();

    renderEmployeeLoanFilterChips();

    if (dataTableInstance) {
        dataTableInstance.draw();
    }

    if (closeModal && typeof bootstrap !== 'undefined') {
        var modalEl = document.getElementById('employeeLoanAdvSearchModal');
        var instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) {
            instance.hide();
        }
    }
}

function applyEmployeeLoanAdvFiltersToRequest(d) {

    if (employeeLoanAdvFilters.employee_id) {
        d.employee_id = employeeLoanAdvFilters.employee_id.value;
    }

    if (employeeLoanAdvFilters.approval_status) {
        d.approval_status = employeeLoanAdvFilters.approval_status.value;
    }

    if (employeeLoanAdvFilters.status) {
        d.status = employeeLoanAdvFilters.status.value;
    }

    if (employeeLoanAdvFilters.payment_state) {
        d.payment_state = employeeLoanAdvFilters.payment_state.value;
    }

    if (employeeLoanAdvFilters.deduct_from_salary) {
        d.deduct_from_salary = employeeLoanAdvFilters.deduct_from_salary.value;
    }

    if (employeeLoanAdvFilters.loan_amount_range) {
        if (employeeLoanAdvFilters.loan_amount_range.value.min !== '') {
            d.loan_amount_min = employeeLoanAdvFilters.loan_amount_range.value.min;
        }
        if (employeeLoanAdvFilters.loan_amount_range.value.max !== '') {
            d.loan_amount_max = employeeLoanAdvFilters.loan_amount_range.value.max;
        }
    }

    if (employeeLoanAdvFilters.start_date) {
        if (employeeLoanAdvFilters.start_date.value.from) {
            d.start_date_from = employeeLoanAdvFilters.start_date.value.from;
        }
        if (employeeLoanAdvFilters.start_date.value.to) {
            d.start_date_to = employeeLoanAdvFilters.start_date.value.to;
        }
    }
}
