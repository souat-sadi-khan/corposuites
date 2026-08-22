var dataTableInstance;

var DataTablePayrolls = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#payrollTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#payrollTable').data('url'),
                data: function (d) {
                    d.search = $('#payrollSearch').val();
                    var employeeId = $('#payrollTable').data('employee-id');
                    if (employeeId) {
                        d.employee_id = employeeId;
                    }

                    applyPayrollAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'employee_name' },
                { data: 'period' },
                { data: 'salary_summary' },
                { data: 'payment_badge' },
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
                        <p class="text-muted mb-0">No payroll records available</p>
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
                bindMarkPaidButtons();
            }
        });
    };

    return {
        init: function () {
            initDataTable();
            _statusUpdate();
            initPayrollAdvSearch();
        }
    };
}();

// =====================================================
// Commission Sales Amount Field (shown only for
// employees on a commission-based salary structure)
// =====================================================
function togglePayrollCommissionField($form) {
    var $select = $form.find('[name="employee_id"]');
    var payType = $select.find('option:selected').data('pay-type');
    var $field = $form.find('.payroll-commission-field');
    var $input = $field.find('[name="commission_sales_amount"]');

    if (payType === 'commission') {
        $field.show();
        $input.prop('required', true);
    } else {
        $field.hide();
        $input.prop('required', false).val('');
    }
}

$(document).on('change', '[name="employee_id"]', function () {
    togglePayrollCommissionField($(this).closest('form'));
    togglePayrollOccurrenceFields($(this).closest('form'));
});

$(document).on('shown.bs.modal', '#modal_remote', function () {
    $(this).find('form.ajax-form').each(function () {
        togglePayrollCommissionField($(this));
        togglePayrollOccurrenceFields($(this));
    });
});

// =====================================================
// Occurrence Count Fields (one per per-occurrence
// component on the selected employee's active structure)
// =====================================================
function togglePayrollOccurrenceFields($form) {

    var $select = $form.find('[name="employee_id"]');
    var $option = $select.find('option:selected');
    var $wrap = $form.find('.payroll-occurrence-fields');
    var $rows = $form.find('.payroll-occurrence-rows');

    var components = [];

    try {
        components = JSON.parse($option.attr('data-occurrence-components') || '[]');
    } catch (e) {
        components = [];
    }

    $rows.empty();

    if (!components.length) {
        $wrap.hide();
        return;
    }

    components.forEach(function (component) {

        var $row = $(
            '<div class="d-flex align-items-center gap-2 mb-2">' +
                '<label class="mb-0 flex-shrink-0" style="min-width:200px;">' +
                    $('<span></span>').text(component.name).html() +
                    ' <small class="text-muted">(' + formatPayrollAmount(component.rate) + '/occurrence)</small>' +
                '</label>' +
                '<input type="number" min="0" step="1" class="form-control form-control-sm" ' +
                    'name="occurrence_counts[' + component.id + ']" placeholder="0" value="0" required>' +
            '</div>'
        );

        $rows.append($row);
    });

    $wrap.show();
}

function formatPayrollAmount(amount) {
    return Number(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// =====================================================
// Mark as Paid
// =====================================================
function bindMarkPaidButtons() {
    $('button#markPaid').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!confirm('Mark this payroll as paid?')) return;
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
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
    DataTablePayrolls.init();

    // Search
    $('#payrollSearch').on('keyup', function () {
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

var payrollAdvFilters = {};

function initPayrollAdvSearch() {

    if (!$('#payrollAdvSearchModal').length) {
        return;
    }

    $('#advEmployee, #advDepartment, #advDesignation').each(function () {
        $(this).select2({
            width: '100%',
            dropdownParent: $('#payrollAdvSearchModal'),
            templateResult: _payrollSelectOptionTemplate
        });
    });

    $('.as-select').select2({
        width: '100%',
        dropdownParent: $('#payrollAdvSearchModal')
    });

    $('#advSearchApply').on('click', function () {
        applyPayrollAdvFilters(true);
    });

    $('#advSearchReset').on('click', function () {
        resetPayrollAdvFieldsUI();
    });

    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete payrollAdvFilters[key];
        clearPayrollAdvField(key);
        renderPayrollFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });

    $(document).on('click', '#advClearAllChips', function () {
        payrollAdvFilters = {};
        resetPayrollAdvFieldsUI();
        renderPayrollFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });
}

function _payrollSelectOptionTemplate(option) {
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

function clearPayrollAdvField(key) {
    switch (key) {
        case 'employee_id':
            $('#advEmployee').val('').trigger('change.select2');
            break;
        case 'department_id':
            $('#advDepartment').val('').trigger('change.select2');
            break;
        case 'designation_id':
            $('#advDesignation').val('').trigger('change.select2');
            break;
        case 'pay_type':
            $('#advPayType').val('').trigger('change.select2');
            break;
        case 'payment_status':
            $('#advPaymentStatus').val('').trigger('change.select2');
            break;
        case 'status':
            $('#advRecordStatus').val('').trigger('change.select2');
            break;
        case 'period':
            $('#advMonth, #advYear').val('').trigger('change.select2');
            break;
        case 'net_salary_range':
            $('#advNetSalaryMin, #advNetSalaryMax').val('');
            break;
    }
}

function resetPayrollAdvFieldsUI() {
    $('#advEmployee, #advDepartment, #advDesignation, #advPayType, #advPaymentStatus, #advRecordStatus, #advMonth, #advYear')
        .val('')
        .trigger('change.select2');

    $('#advNetSalaryMin, #advNetSalaryMax').val('');
}

var payrollMonthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function collectPayrollAdvFilters() {

    var filters = {};

    var $employee = $('#advEmployee');
    if ($employee.val()) {
        filters.employee_id = {
            value: $employee.val(),
            label: 'Employee: ' + $employee.find('option:selected').text()
        };
    }

    var $department = $('#advDepartment');
    if ($department.val()) {
        filters.department_id = {
            value: $department.val(),
            label: 'Department: ' + $department.find('option:selected').text()
        };
    }

    var $designation = $('#advDesignation');
    if ($designation.val()) {
        filters.designation_id = {
            value: $designation.val(),
            label: 'Designation: ' + $designation.find('option:selected').text()
        };
    }

    var $payType = $('#advPayType');
    if ($payType.val()) {
        filters.pay_type = {
            value: $payType.val(),
            label: 'Pay Type: ' + $payType.find('option:selected').text()
        };
    }

    var $paymentStatus = $('#advPaymentStatus');
    if ($paymentStatus.val()) {
        filters.payment_status = {
            value: $paymentStatus.val(),
            label: 'Reimbursement: ' + $paymentStatus.find('option:selected').text()
        };
    }

    var $recordStatus = $('#advRecordStatus');
    if ($recordStatus.val() !== '') {
        filters.status = {
            value: $recordStatus.val(),
            label: 'Record Status: ' + $recordStatus.find('option:selected').text()
        };
    }

    var month = $('#advMonth').val();
    var year = $('#advYear').val();
    if (month || year) {
        var label = 'Period: ' + (month ? payrollMonthNames[parseInt(month, 10)] : 'Any month') + ' ' + (year || '(any year)');
        filters.period = {
            value: { month: month, year: year },
            label: label
        };
    }

    var netMin = $('#advNetSalaryMin').val();
    var netMax = $('#advNetSalaryMax').val();
    if (netMin !== '' || netMax !== '') {
        filters.net_salary_range = {
            value: { min: netMin, max: netMax },
            label: 'Net Salary: ' + (netMin !== '' ? netMin : '0') + ' - ' + (netMax !== '' ? netMax : '∞')
        };
    }

    return filters;
}

function renderPayrollFilterChips() {

    var $bar = $('#advSearchChipsBar');
    var $chips = $('#advSearchChips');
    var keys = Object.keys(payrollAdvFilters);

    $chips.empty();

    if (!keys.length) {
        $bar.hide();
        $('#advSearchBadge').hide();
        return;
    }

    keys.forEach(function (key) {

        var filter = payrollAdvFilters[key];

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

function applyPayrollAdvFilters(closeModal) {

    payrollAdvFilters = collectPayrollAdvFilters();

    renderPayrollFilterChips();

    if (dataTableInstance) {
        dataTableInstance.draw();
    }

    if (closeModal && typeof bootstrap !== 'undefined') {
        var modalEl = document.getElementById('payrollAdvSearchModal');
        var instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) {
            instance.hide();
        }
    }
}

function applyPayrollAdvFiltersToRequest(d) {

    if (payrollAdvFilters.employee_id) {
        d.employee_id = payrollAdvFilters.employee_id.value;
    }

    if (payrollAdvFilters.department_id) {
        d.department_id = payrollAdvFilters.department_id.value;
    }

    if (payrollAdvFilters.designation_id) {
        d.designation_id = payrollAdvFilters.designation_id.value;
    }

    if (payrollAdvFilters.pay_type) {
        d.pay_type = payrollAdvFilters.pay_type.value;
    }

    if (payrollAdvFilters.payment_status) {
        d.payment_status = payrollAdvFilters.payment_status.value;
    }

    if (payrollAdvFilters.status) {
        d.status = payrollAdvFilters.status.value;
    }

    if (payrollAdvFilters.period) {
        if (payrollAdvFilters.period.value.month) {
            d.month = payrollAdvFilters.period.value.month;
        }
        if (payrollAdvFilters.period.value.year) {
            d.year = payrollAdvFilters.period.value.year;
        }
    }

    if (payrollAdvFilters.net_salary_range) {
        if (payrollAdvFilters.net_salary_range.value.min !== '') {
            d.net_salary_min = payrollAdvFilters.net_salary_range.value.min;
        }
        if (payrollAdvFilters.net_salary_range.value.max !== '') {
            d.net_salary_max = payrollAdvFilters.net_salary_range.value.max;
        }
    }
}
