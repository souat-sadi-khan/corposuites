var dataTableInstance;

var DataTableExpenseClaims = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#expenseClaimTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#expenseClaimTable').data('url'),
                data: function (d) {
                    d.search = $('#expenseClaimSearch').val();
                    var employeeId = $('#expenseClaimTable').data('employee-id');
                    if (employeeId) {
                        d.employee_id = employeeId;
                    }

                    applyExpenseClaimAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'employee_name' },
                { data: 'category_summary' },
                { data: 'expense_date_formatted' },
                { data: 'receipt_link' },
                { data: 'approval_badge' },
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
                        <p class="text-muted mb-0">No expense claims available</p>
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
                bindMarkReimbursedButton();
            }
        });
    };

    return {
        init: function () {
            initDataTable();
            _statusUpdate();
            initExpenseClaimAdvSearch();
        }
    };
}();

// =====================================================
// Approve / Reject
// =====================================================
function bindApprovalButtons() {
    $('button#approveExpenseClaim, button#rejectExpenseClaim').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!confirm('Are you sure you want to perform this action?')) return;
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
// Mark Reimbursed
// =====================================================
function bindMarkReimbursedButton() {
    $('button#markReimbursedExpenseClaim').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!confirm('Mark this claim as reimbursed to the employee?')) return;
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
    DataTableExpenseClaims.init();

    // Search
    $('#expenseClaimSearch').on('keyup', function () {
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

var expenseClaimAdvFilters = {};

function initExpenseClaimAdvSearch() {

    if (!$('#expenseClaimAdvSearchModal').length) {
        return;
    }

    $('#advEmployee, #advCategory').each(function () {
        $(this).select2({
            width: '100%',
            dropdownParent: $('#expenseClaimAdvSearchModal'),
            templateResult: _expenseClaimSelectOptionTemplate
        });
    });

    $('.as-select').select2({
        width: '100%',
        dropdownParent: $('#expenseClaimAdvSearchModal')
    });

    $('#advSearchApply').on('click', function () {
        applyExpenseClaimAdvFilters(true);
    });

    $('#advSearchReset').on('click', function () {
        resetExpenseClaimAdvFieldsUI();
    });

    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete expenseClaimAdvFilters[key];
        clearExpenseClaimAdvField(key);
        renderExpenseClaimFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });

    $(document).on('click', '#advClearAllChips', function () {
        expenseClaimAdvFilters = {};
        resetExpenseClaimAdvFieldsUI();
        renderExpenseClaimFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });
}

function _expenseClaimSelectOptionTemplate(option) {
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

function clearExpenseClaimAdvField(key) {
    switch (key) {
        case 'employee_id':
            $('#advEmployee').val('').trigger('change.select2');
            break;
        case 'expense_category_id':
            $('#advCategory').val('').trigger('change.select2');
            break;
        case 'approval_status':
            $('#advApprovalStatus').val('').trigger('change.select2');
            break;
        case 'payment_status':
            $('#advPaymentStatus').val('').trigger('change.select2');
            break;
        case 'has_receipt':
            $('#advHasReceipt').val('').trigger('change.select2');
            break;
        case 'status':
            $('#advRecordStatus').val('').trigger('change.select2');
            break;
        case 'expense_date':
            $('#advDateFrom, #advDateTo').val('');
            break;
        case 'amount_range':
            $('#advAmountMin, #advAmountMax').val('');
            break;
    }
}

function resetExpenseClaimAdvFieldsUI() {
    $('#advEmployee, #advCategory, #advApprovalStatus, #advPaymentStatus, #advHasReceipt, #advRecordStatus')
        .val('')
        .trigger('change.select2');

    $('#advDateFrom, #advDateTo, #advAmountMin, #advAmountMax').val('');
}

function collectExpenseClaimAdvFilters() {

    var filters = {};

    var $employee = $('#advEmployee');
    if ($employee.val()) {
        filters.employee_id = {
            value: $employee.val(),
            label: 'Employee: ' + $employee.find('option:selected').text()
        };
    }

    var $category = $('#advCategory');
    if ($category.val()) {
        filters.expense_category_id = {
            value: $category.val(),
            label: 'Category: ' + $category.find('option:selected').text()
        };
    }

    var $approvalStatus = $('#advApprovalStatus');
    if ($approvalStatus.val()) {
        filters.approval_status = {
            value: $approvalStatus.val(),
            label: 'Approval: ' + $approvalStatus.find('option:selected').text()
        };
    }

    var $paymentStatus = $('#advPaymentStatus');
    if ($paymentStatus.val()) {
        filters.payment_status = {
            value: $paymentStatus.val(),
            label: 'Reimbursement: ' + $paymentStatus.find('option:selected').text()
        };
    }

    var $hasReceipt = $('#advHasReceipt');
    if ($hasReceipt.val() !== '') {
        filters.has_receipt = {
            value: $hasReceipt.val(),
            label: 'Has Receipt: ' + $hasReceipt.find('option:selected').text()
        };
    }

    var $recordStatus = $('#advRecordStatus');
    if ($recordStatus.val() !== '') {
        filters.status = {
            value: $recordStatus.val(),
            label: 'Record Status: ' + $recordStatus.find('option:selected').text()
        };
    }

    var dateFrom = $('#advDateFrom').val();
    var dateTo = $('#advDateTo').val();
    if (dateFrom || dateTo) {
        filters.expense_date = {
            value: { from: dateFrom, to: dateTo },
            label: 'Date: ' + (dateFrom || '…') + ' → ' + (dateTo || '…')
        };
    }

    var amountMin = $('#advAmountMin').val();
    var amountMax = $('#advAmountMax').val();
    if (amountMin !== '' || amountMax !== '') {
        filters.amount_range = {
            value: { min: amountMin, max: amountMax },
            label: 'Amount: ' + (amountMin !== '' ? amountMin : '0') + ' - ' + (amountMax !== '' ? amountMax : '∞')
        };
    }

    return filters;
}

function renderExpenseClaimFilterChips() {

    var $bar = $('#advSearchChipsBar');
    var $chips = $('#advSearchChips');
    var keys = Object.keys(expenseClaimAdvFilters);

    $chips.empty();

    if (!keys.length) {
        $bar.hide();
        $('#advSearchBadge').hide();
        return;
    }

    keys.forEach(function (key) {

        var filter = expenseClaimAdvFilters[key];

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

function applyExpenseClaimAdvFilters(closeModal) {

    expenseClaimAdvFilters = collectExpenseClaimAdvFilters();

    renderExpenseClaimFilterChips();

    if (dataTableInstance) {
        dataTableInstance.draw();
    }

    if (closeModal && typeof bootstrap !== 'undefined') {
        var modalEl = document.getElementById('expenseClaimAdvSearchModal');
        var instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) {
            instance.hide();
        }
    }
}

function applyExpenseClaimAdvFiltersToRequest(d) {

    if (expenseClaimAdvFilters.employee_id) {
        d.employee_id = expenseClaimAdvFilters.employee_id.value;
    }

    if (expenseClaimAdvFilters.expense_category_id) {
        d.expense_category_id = expenseClaimAdvFilters.expense_category_id.value;
    }

    if (expenseClaimAdvFilters.approval_status) {
        d.approval_status = expenseClaimAdvFilters.approval_status.value;
    }

    if (expenseClaimAdvFilters.payment_status) {
        d.payment_status = expenseClaimAdvFilters.payment_status.value;
    }

    if (expenseClaimAdvFilters.has_receipt) {
        d.has_receipt = expenseClaimAdvFilters.has_receipt.value;
    }

    if (expenseClaimAdvFilters.status) {
        d.status = expenseClaimAdvFilters.status.value;
    }

    if (expenseClaimAdvFilters.expense_date) {
        if (expenseClaimAdvFilters.expense_date.value.from) {
            d.date_from = expenseClaimAdvFilters.expense_date.value.from;
        }
        if (expenseClaimAdvFilters.expense_date.value.to) {
            d.date_to = expenseClaimAdvFilters.expense_date.value.to;
        }
    }

    if (expenseClaimAdvFilters.amount_range) {
        if (expenseClaimAdvFilters.amount_range.value.min !== '') {
            d.amount_min = expenseClaimAdvFilters.amount_range.value.min;
        }
        if (expenseClaimAdvFilters.amount_range.value.max !== '') {
            d.amount_max = expenseClaimAdvFilters.amount_range.value.max;
        }
    }
}
