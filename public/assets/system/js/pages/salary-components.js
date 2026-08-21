var dataTableInstance;

var DataTableSalaryComponents = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#salaryComponentTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#salaryComponentTable').data('url'),
                data: function (d) {
                    d.search = $('#salaryComponentSearch').val();
                    applyComponentAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
                { data: 'type_badge' },
                { data: 'value_formatted' },
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
                        <p class="text-muted mb-0">No salary components available</p>
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
                if (typeof _componentRemoteOffcanvasLoadAfterAjax === 'function') {
                    _componentRemoteOffcanvasLoadAfterAjax();
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
// Calculation Type — Value label/help text
// =====================================================

var SALARY_COMPONENT_CALC_TYPE_LABELS = {
    fixed: {
        label: 'Value',
        help: 'A flat amount added to (earning) or deducted from (deduction) each pay period.'
    },
    percentage: {
        label: 'Value (%)',
        help: 'A percentage of the employee\'s basic salary for that period, recalculated automatically each payroll run.'
    },
    per_occurrence: {
        label: 'Rate per Occurrence',
        help: 'A flat rate multiplied by however many times this happens in a period (e.g. "$10 per late day") — the count is entered when generating each employee\'s payroll.'
    }
};

function updateSalaryComponentValueUi(form) {

    var $form = $(form);

    var calcType = $form
        .find('[name="calculation_type"]')
        .val() || 'fixed';

    var config = SALARY_COMPONENT_CALC_TYPE_LABELS[calcType] || SALARY_COMPONENT_CALC_TYPE_LABELS.fixed;

    $form.find('.salary-component-value-label').contents().first().replaceWith(config.label + ' ');
    $form.find('.salary-component-value-help').text(config.help);
}

$(document).on('change', '.salary-component-calc-type', function () {
    updateSalaryComponentValueUi($(this).closest('form'));
});

$(document).on('shown.bs.modal', '#modal_remote', function () {

    $(this).find('form.ajax-form').each(function () {
        updateSalaryComponentValueUi($(this));
    });

});

$('.as-select').select2({
    width: '100%',
    dropdownParent: $('#componentAdvSearchModal')
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
    DataTableSalaryComponents.init();

    // Search
    $('#salaryComponentSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous / Next
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    // Advanced Search
    initComponentAdvSearch();
});


// =====================================================
// Advanced Search — state, chips, apply/reset
// =====================================================

var componentAdvFilters = {};

function initComponentAdvSearch() {

    if (!$('#componentAdvSearchModal').length) {
        return;
    }

    $('#advSearchApply').on('click', function () {
        applyComponentAdvFilters(true);
    });

    $('#advSearchReset').on('click', function () {
        resetComponentAdvFieldsUI();
    });

    $(document).on('click', '.adv-chip-remove', function () {

        var key = $(this).data('key');

        delete componentAdvFilters[key];

        clearComponentAdvField(key);

        renderComponentFilterChips();

        if (dataTableInstance) {
            dataTableInstance.draw();
        }

    });

    $(document).on('click', '#advClearAllChips', function () {

        componentAdvFilters = {};

        resetComponentAdvFieldsUI();

        renderComponentFilterChips();

        if (dataTableInstance) {
            dataTableInstance.draw();
        }

    });
}

function clearComponentAdvField(key) {

    switch (key) {

        case 'type':
            $('#advType').val('');
            break;

        case 'calculation_type':
            $('#advCalculationType').val('');
            break;

        case 'value_range':
            $('#advValueMin, #advValueMax').val('');
            break;

        case 'is_taxable':
            $('#advTaxable').val('');
            break;

        case 'status':
            $('#advStatus').val('');
            break;
    }
}

function resetComponentAdvFieldsUI() {

    $('#advType, #advCalculationType, #advTaxable, #advStatus').val('');
    $('#advValueMin, #advValueMax').val('');
}

function collectComponentAdvFilters() {

    var filters = {};

    var $type = $('#advType');

    if ($type.val()) {
        filters.type = {
            value: $type.val(),
            label: 'Type: ' + $type.find('option:selected').text()
        };
    }

    var $calcType = $('#advCalculationType');

    if ($calcType.val()) {
        filters.calculation_type = {
            value: $calcType.val(),
            label: 'Calculation: ' + $calcType.find('option:selected').text()
        };
    }

    var valueMin = $('#advValueMin').val();
    var valueMax = $('#advValueMax').val();

    if (valueMin !== '' || valueMax !== '') {
        filters.value_range = {
            value: { min: valueMin, max: valueMax },
            label: 'Value: ' + (valueMin !== '' ? valueMin : '0') + ' - ' + (valueMax !== '' ? valueMax : '∞')
        };
    }

    var $taxable = $('#advTaxable');

    if ($taxable.val() !== '') {
        filters.is_taxable = {
            value: $taxable.val(),
            label: $taxable.find('option:selected').text()
        };
    }

    var $status = $('#advStatus');

    if ($status.val() !== '') {
        filters.status = {
            value: $status.val(),
            label: 'Status: ' + $status.find('option:selected').text()
        };
    }

    return filters;
}

function renderComponentFilterChips() {

    var $bar = $('#advSearchChipsBar');
    var $chips = $('#advSearchChips');
    var keys = Object.keys(componentAdvFilters);

    $chips.empty();

    if (!keys.length) {
        $bar.hide();
        $('#advSearchBadge').hide();
        return;
    }

    keys.forEach(function (key) {

        var filter = componentAdvFilters[key];

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

function applyComponentAdvFilters(closeModal) {

    componentAdvFilters = collectComponentAdvFilters();

    renderComponentFilterChips();

    if (dataTableInstance) {
        dataTableInstance.draw();
    }

    if (closeModal && typeof bootstrap !== 'undefined') {

        var modalEl = document.getElementById('componentAdvSearchModal');
        var instance = bootstrap.Modal.getInstance(modalEl);

        if (instance) {
            instance.hide();
        }
    }
}

function applyComponentAdvFiltersToRequest(d) {

    if (componentAdvFilters.type) {
        d.type = componentAdvFilters.type.value;
    }

    if (componentAdvFilters.calculation_type) {
        d.calculation_type = componentAdvFilters.calculation_type.value;
    }

    if (componentAdvFilters.value_range) {

        if (componentAdvFilters.value_range.value.min !== '') {
            d.value_min = componentAdvFilters.value_range.value.min;
        }

        if (componentAdvFilters.value_range.value.max !== '') {
            d.value_max = componentAdvFilters.value_range.value.max;
        }
    }

    if (componentAdvFilters.is_taxable) {
        d.is_taxable = componentAdvFilters.is_taxable.value;
    }

    if (componentAdvFilters.status) {
        d.status = componentAdvFilters.status.value;
    }
}
