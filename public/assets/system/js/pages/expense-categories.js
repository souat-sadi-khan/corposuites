var dataTableInstance;

var DataTableExpenseCategories = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#expenseCategoryTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#expenseCategoryTable').data('url'),
                data: function (d) {
                    d.search = $('#expenseCategorySearch').val();
                    applyExpenseCategoryAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name_col' },
                { data: 'policy_col' },
                { data: 'gl_account_col' },
                { data: 'claims_count_label' },
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
                        <p class="text-muted mb-0">No expense categories yet</p>
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
            initExpenseCategoryAdvSearch();
        }
    };
}();

function updateTlInfo() {
    var info = dataTableInstance.page.info();
    var start = info.recordsDisplay === 0 ? 0 : info.start + 1;
    $('#tlInfo').text(start + ' - ' + info.end + ' of ' + info.recordsDisplay);
    $('#tlPrev').prop('disabled', info.page === 0);
    $('#tlNext').prop('disabled', info.page >= info.pages - 1 || info.pages === 0);
}

document.addEventListener('DOMContentLoaded', function () {
    DataTableExpenseCategories.init();

    $('#expenseCategorySearch').on('keyup', function () {
        dataTableInstance.draw();
    });

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

var expenseCategoryAdvFilters = {};

function initExpenseCategoryAdvSearch() {

    if (!$('#expenseCategoryAdvSearchModal').length) {
        return;
    }

    $('#advChartOfAccount').select2({
        width: '100%',
        dropdownParent: $('#expenseCategoryAdvSearchModal')
    });

    $('.as-select').select2({
        width: '100%',
        dropdownParent: $('#expenseCategoryAdvSearchModal')
    });

    $('#advSearchApply').on('click', function () {
        applyExpenseCategoryAdvFilters(true);
    });

    $('#advSearchReset').on('click', function () {
        resetExpenseCategoryAdvFieldsUI();
    });

    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete expenseCategoryAdvFilters[key];
        clearExpenseCategoryAdvField(key);
        renderExpenseCategoryFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });

    $(document).on('click', '#advClearAllChips', function () {
        expenseCategoryAdvFilters = {};
        resetExpenseCategoryAdvFieldsUI();
        renderExpenseCategoryFilterChips();
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });
}

function clearExpenseCategoryAdvField(key) {
    switch (key) {
        case 'has_limit':
            $('#advHasLimit').val('').trigger('change.select2');
            break;
        case 'receipt_required':
            $('#advReceiptRequired').val('').trigger('change.select2');
            break;
        case 'max_amount_range':
            $('#advMaxAmountMin, #advMaxAmountMax').val('');
            break;
        case 'chart_of_account_id':
            $('#advChartOfAccount').val('').trigger('change.select2');
            break;
        case 'status':
            $('#advRecordStatus').val('').trigger('change.select2');
            break;
    }
}

function resetExpenseCategoryAdvFieldsUI() {
    $('#advHasLimit, #advReceiptRequired, #advChartOfAccount, #advRecordStatus')
        .val('')
        .trigger('change.select2');

    $('#advMaxAmountMin, #advMaxAmountMax').val('');
}

function collectExpenseCategoryAdvFilters() {

    var filters = {};

    var $hasLimit = $('#advHasLimit');
    if ($hasLimit.val() !== '') {
        filters.has_limit = {
            value: $hasLimit.val(),
            label: 'Spending Cap: ' + $hasLimit.find('option:selected').text()
        };
    }

    var $receiptRequired = $('#advReceiptRequired');
    if ($receiptRequired.val() !== '') {
        filters.receipt_required = {
            value: $receiptRequired.val(),
            label: 'Receipt Threshold: ' + $receiptRequired.find('option:selected').text()
        };
    }

    var maxMin = $('#advMaxAmountMin').val();
    var maxMax = $('#advMaxAmountMax').val();
    if (maxMin !== '' || maxMax !== '') {
        filters.max_amount_range = {
            value: { min: maxMin, max: maxMax },
            label: 'Max Amount: ' + (maxMin !== '' ? maxMin : '0') + ' - ' + (maxMax !== '' ? maxMax : '∞')
        };
    }

    var $chartOfAccount = $('#advChartOfAccount');
    if ($chartOfAccount.val()) {
        filters.chart_of_account_id = {
            value: $chartOfAccount.val(),
            label: 'GL Account: ' + $chartOfAccount.find('option:selected').text()
        };
    }

    var $recordStatus = $('#advRecordStatus');
    if ($recordStatus.val() !== '') {
        filters.status = {
            value: $recordStatus.val(),
            label: 'Record Status: ' + $recordStatus.find('option:selected').text()
        };
    }

    return filters;
}

function renderExpenseCategoryFilterChips() {

    var $bar = $('#advSearchChipsBar');
    var $chips = $('#advSearchChips');
    var keys = Object.keys(expenseCategoryAdvFilters);

    $chips.empty();

    if (!keys.length) {
        $bar.hide();
        $('#advSearchBadge').hide();
        return;
    }

    keys.forEach(function (key) {

        var filter = expenseCategoryAdvFilters[key];

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

function applyExpenseCategoryAdvFilters(closeModal) {

    expenseCategoryAdvFilters = collectExpenseCategoryAdvFilters();

    renderExpenseCategoryFilterChips();

    if (dataTableInstance) {
        dataTableInstance.draw();
    }

    if (closeModal && typeof bootstrap !== 'undefined') {
        var modalEl = document.getElementById('expenseCategoryAdvSearchModal');
        var instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) {
            instance.hide();
        }
    }
}

function applyExpenseCategoryAdvFiltersToRequest(d) {

    if (expenseCategoryAdvFilters.has_limit) {
        d.has_limit = expenseCategoryAdvFilters.has_limit.value;
    }

    if (expenseCategoryAdvFilters.receipt_required) {
        d.receipt_required = expenseCategoryAdvFilters.receipt_required.value;
    }

    if (expenseCategoryAdvFilters.max_amount_range) {
        if (expenseCategoryAdvFilters.max_amount_range.value.min !== '') {
            d.max_amount_min = expenseCategoryAdvFilters.max_amount_range.value.min;
        }
        if (expenseCategoryAdvFilters.max_amount_range.value.max !== '') {
            d.max_amount_max = expenseCategoryAdvFilters.max_amount_range.value.max;
        }
    }

    if (expenseCategoryAdvFilters.chart_of_account_id) {
        d.chart_of_account_id = expenseCategoryAdvFilters.chart_of_account_id.value;
    }

    if (expenseCategoryAdvFilters.status) {
        d.status = expenseCategoryAdvFilters.status.value;
    }
}
