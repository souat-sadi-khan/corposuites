var dataTableInstance;

var DataTableSalaryTemplates = function () {

    var initDataTable = function () {

        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#salaryTemplateTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[1, 'asc']],

            ajax: {
                url: $('#salaryTemplateTable').data('url'),

                data: function (d) {

                    d.search = $('#salaryTemplateSearch').val();

                    var statuses = [];

                    $('#tlFilterDd input:checked').each(function () {
                        statuses.push($(this).val());
                    });

                    if (statuses.length) {
                        d.status = statuses.join(',');
                    }

                    var payType = $('#salaryTemplatePayTypeFilter').val();

                    if (payType) {
                        d.pay_type = payType;
                    }
                }
            },

            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
                { data: 'pay_type_badge' },
                { data: 'salary_summary' },
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
                        <img src="${window.location.origin}/assets/images/nothing-to-show.svg"
                             class="img-fluid mb-2"
                             style="max-width:150px">
                        <p class="text-muted mb-0">
                            No salary templates available
                        </p>
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
// Pay Type — Basic Salary Label/Help Text
// =====================================================

var SALARY_TEMPLATE_PAY_TYPE_LABELS = {
    monthly: {
        label: 'Basic Salary',
        summary: 'Basic Salary',
        help: 'The fixed monthly amount every employee this template is applied to will receive.'
    },
    daily: {
        label: 'Daily Rate',
        summary: 'Daily Rate',
        help: 'Rate paid per day worked, applied to whichever employees receive this template.'
    },
    commission: {
        label: 'Commission Rate (%)',
        summary: 'Commission Rate',
        help: 'Percentage applied against the sales amount entered when generating payroll.'
    }
};

function updateSalaryTemplatePayTypeUi(form) {

    var $form = $(form);

    var payType = $form
        .find('[name="pay_type"]')
        .val() || 'monthly';

    var config = SALARY_TEMPLATE_PAY_TYPE_LABELS[payType] || SALARY_TEMPLATE_PAY_TYPE_LABELS.monthly;

    $form.find('.salary-template-basic-label').contents().first().replaceWith(config.label + ' ');
    $form.find('.salary-template-basic-help').text(config.help);
    $form.find('.salary-template-basic-summary-label').text(config.summary);

    var $basicInput = $form.find('.salary-template-basic-input');

    if (payType === 'commission') {
        $basicInput.attr('max', 100);
    } else {
        $basicInput.removeAttr('max');
    }
}

$(document).on('change', '.salary-template-pay-type', function () {
    updateSalaryTemplatePayTypeUi($(this).closest('form'));
});


// =====================================================
// Salary Component Row Index
// =====================================================

var salaryTemplateComponentRowIndex = 0;


// =====================================================
// Build Salary Component Row
// =====================================================

function buildSalaryTemplateComponentRow(container, componentId, amount) {

    var $container = $(container);
    var $form = $container.closest('form');
    var optionsHtml = $form.find('.salary-template-component-options').html();
    var index = salaryTemplateComponentRowIndex++;

    var row = $(`
        <div class="salary-component-row mb-2">
            <div class="fm-field">
                <select
                    name="components[${index}][salary_component_id]"
                    class="form-select salary-template-select"
                    data-placeholder="Select Component">
                    ${optionsHtml}
                </select>
            </div>
            <div class="fm-field">
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control salary-template-component-amount"
                    name="components[${index}][amount]"
                    placeholder="Amount"
                    value="0">
            </div>
            <div class="fm-field salary-component-action">
                <button type="button" class="btn-nx-outline btn-sm remove-salary-template-component">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    $container.append(row);

    var $select = row.find('.salary-template-select');
    var $amount = row.find('.salary-template-component-amount');

    if (componentId) {
        $select.val(componentId);
    }

    if (amount !== undefined && amount !== null) {
        $amount.val(amount).data('manual-value', true);
    }

    initSalaryTemplateComponentSelect(row);

    if (componentId) {
        updateSalaryTemplateComponentOptions($container);
    }

    calculateSalaryTemplateTotals($form);
}


// =====================================================
// Initialize Salary Component Select2
// =====================================================

function initSalaryTemplateComponentSelect(row) {

    var $select = $(row).find('.salary-template-select');

    if ($select.hasClass('select2-hidden-accessible')) {
        return;
    }

    $select.select2({
        dropdownParent: $('#modal_remote'),
        templateResult: _selectOptionTemplateSalaryTemplate,
        templateSelection: function (option) {
            return option.text;
        }
    });
}


// =====================================================
// Salary Component Select2 Template
// =====================================================

function _selectOptionTemplateSalaryTemplate(option) {

    if (!option.id || !option.element) {
        return option.text;
    }

    var $option = $(option.element);
    var desc = $option.attr('data-desc');
    var type = $option.attr('data-type');
    var value = parseFloat($option.attr('data-value')) || 0;
    var calculationType = $option.attr('data-calculation-type');
    var isEarning = type === 'earning';
    var typeClass = isEarning ? 'text-success' : 'text-danger';
    var typeBgClass = isEarning ? 'bg-success-subtle' : 'bg-danger-subtle';

    var $opt = $('<div class="sel-opt-rich"></div>');
    var $info = $('<div class="sel-opt-rich-info"></div>');
    var $nameRow = $('<div class="sel-opt-rich-name-row"></div>');

    $nameRow.append($('<div class="sel-opt-rich-name"></div>').text(option.text));

    $nameRow.append(
        $('<span class="sel-opt-type-badge"></span>')
            .addClass(typeClass)
            .addClass(typeBgClass)
            .text(isEarning ? 'Earning' : 'Deduction')
    );

    var valueLabel = calculationType === 'percentage' ? value + '%' : 'Fixed ' + value.toFixed(2);

    $nameRow.append($('<span class="sel-opt-percentage"></span>').text(valueLabel));

    $info.append($nameRow);

    if (desc) {
        $info.append($('<div class="sel-opt-rich-desc"></div>').text(desc));
    }

    $opt.append($info);

    return $opt;
}


// =====================================================
// Add Salary Component
// =====================================================

$(document).on('click', '.salary-template-component-add', function () {

    // The button lives in the modal footer (not the body), so it can't
    // be found via .closest('.modal-body, .offcanvas-body') — walk up to
    // the whole form instead, which wraps both the footer and the body.
    var container = $(this)
        .closest('form')
        .find('.salary-template-component-rows');

    if (!container.length) {
        return;
    }

    buildSalaryTemplateComponentRow(container);
});


// =====================================================
// Component Change
// =====================================================

$(document).on('change', '.salary-template-select', function () {

    var $select = $(this);
    var $row = $select.closest('.salary-component-row');
    var $container = $row.closest('.salary-template-component-rows');
    var $form = $select.closest('form');
    var componentId = $select.val();

    if (!componentId) {
        $row.find('.salary-template-component-amount').val(0).removeData('manual-value');
        updateSalaryTemplateComponentOptions($container);
        calculateSalaryTemplateTotals($form);
        return;
    }

    var duplicate = false;

    $container.find('.salary-template-select').not($select).each(function () {
        if ($(this).val() == componentId) {
            duplicate = true;
            return false;
        }
    });

    if (duplicate) {
        alert('This salary component has already been selected.');
        $select.val('').trigger('change.select2');
        $row.find('.salary-template-component-amount').val(0).removeData('manual-value');
        updateSalaryTemplateComponentOptions($container);
        calculateSalaryTemplateTotals($form);
        return;
    }

    var $option = $select.find('option:selected');
    var value = parseFloat($option.attr('data-value')) || 0;
    var calculationType = $option.attr('data-calculation-type');
    var basicSalary = parseFloat($form.find('[name="basic_salary"]').val()) || 0;
    var amount = calculationType === 'percentage' ? (basicSalary * value) / 100 : value;

    $row.find('.salary-template-component-amount').val(amount.toFixed(2)).removeData('manual-value');

    updateSalaryTemplateComponentOptions($container);
    calculateSalaryTemplateTotals($form);
});


// =====================================================
// Disable Already Selected Components
// =====================================================

function updateSalaryTemplateComponentOptions(container) {

    var $container = $(container);
    var selectedIds = [];

    $container.find('.salary-template-select').each(function () {
        var value = $(this).val();
        if (value) {
            selectedIds.push(String(value));
        }
    });

    $container.find('.salary-template-select').each(function () {

        var $select = $(this);
        var currentValue = String($select.val() || '');

        $select.find('option').each(function () {

            var $option = $(this);
            var optionValue = String($option.val() || '');

            if (!optionValue) {
                return;
            }

            if (optionValue === currentValue) {
                $option.prop('disabled', false);
                return;
            }

            $option.prop('disabled', selectedIds.includes(optionValue));
        });

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        }
    });
}


// =====================================================
// Remove Salary Component
// =====================================================

$(document).on('click', '.remove-salary-template-component', function () {

    var $row = $(this).closest('.salary-component-row');
    var $container = $row.closest('.salary-template-component-rows');
    var $form = $row.closest('form');

    $row.remove();

    updateSalaryTemplateComponentOptions($container);
    calculateSalaryTemplateTotals($form);
});


// =====================================================
// Manual Amount Change
// =====================================================

$(document).on('input', '.salary-template-component-amount', function () {

    var $amount = $(this);
    $amount.data('manual-value', true);
    calculateSalaryTemplateTotals($amount.closest('form'));
});


// =====================================================
// Basic Salary Change
// =====================================================

$(document).on('input', '.salary-template-basic-input', function () {

    var $form = $(this).closest('form');
    recalculateSalaryTemplateComponents($form);
    calculateSalaryTemplateTotals($form);
});


// =====================================================
// Recalculate Existing Components
// =====================================================

function recalculateSalaryTemplateComponents(form) {

    var $form = $(form);
    var basicSalary = parseFloat($form.find('[name="basic_salary"]').val()) || 0;

    $form.find('.salary-component-row').each(function () {

        var $row = $(this);
        var $select = $row.find('.salary-template-select');
        var $amount = $row.find('.salary-template-component-amount');

        if (!$select.val()) {
            return;
        }

        if ($amount.data('manual-value') === true) {
            return;
        }

        var $option = $select.find('option:selected');
        var value = parseFloat($option.attr('data-value')) || 0;
        var calculationType = $option.attr('data-calculation-type');
        var amount = calculationType === 'percentage' ? (basicSalary * value) / 100 : value;

        $amount.val(amount.toFixed(2));
    });
}


// =====================================================
// Calculate Salary Totals
// =====================================================

function calculateSalaryTemplateTotals(form) {

    var $form = $(form);

    if (!$form.length) {
        return;
    }

    var basicSalary = parseFloat($form.find('[name="basic_salary"]').val()) || 0;
    var totalEarnings = 0;
    var totalDeductions = 0;

    $form.find('.salary-component-row').each(function () {

        var $row = $(this);
        var $select = $row.find('.salary-template-select');

        if (!$select.val()) {
            return;
        }

        var amount = parseFloat($row.find('.salary-template-component-amount').val()) || 0;
        var type = $select.find('option:selected').attr('data-type');

        if (type === 'earning') {
            totalEarnings += amount;
        } else if (type === 'deduction') {
            totalDeductions += amount;
        }
    });

    var grossSalary = basicSalary + totalEarnings - totalDeductions;

    $form.find('.salary-template-basic-total').text(formatSalaryTemplateAmount(basicSalary));
    $form.find('.salary-template-earning-total').text(formatSalaryTemplateAmount(totalEarnings));
    $form.find('.salary-template-deduction-total').text(formatSalaryTemplateAmount(totalDeductions));
    $form.find('.salary-template-gross-total').text(formatSalaryTemplateAmount(grossSalary));
}


// =====================================================
// Format Salary Amount
// =====================================================

function formatSalaryTemplateAmount(amount) {
    return Number(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}


// =====================================================
// Populate Existing Salary Components
// =====================================================

function populateExistingSalaryTemplateComponents(scope) {

    $(scope).find('.salary-template-component-rows[data-existing]').each(function () {

        var container = $(this);

        if (container.data('populated')) {
            return;
        }

        container.data('populated', true);

        var existing = [];

        try {
            existing = JSON.parse(container.attr('data-existing')) || [];
        } catch (e) {
            existing = [];
        }

        existing.forEach(function (item) {
            buildSalaryTemplateComponentRow(container, item.salary_component_id, item.amount);
        });

        var $form = container.closest('form');

        updateSalaryTemplateComponentOptions(container);
        calculateSalaryTemplateTotals($form);
    });
}


// =====================================================
// Observe Remote Modal
// =====================================================

(function observeModalContent() {

    var modalContent = document.querySelector('#modal_remote .modal-content');

    if (!modalContent || typeof MutationObserver === 'undefined') {
        return;
    }

    new MutationObserver(function () {
        populateExistingSalaryTemplateComponents(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });

})();


// =====================================================
// Modal Shown - Initial State
// =====================================================

$(document).on('shown.bs.modal', '#modal_remote', function () {

    $(this).find('form.ajax-form').each(function () {

        var $form = $(this);

        updateSalaryTemplatePayTypeUi($form);

        $form.find('.salary-template-component-rows').each(function () {
            updateSalaryTemplateComponentOptions($(this));
        });

        calculateSalaryTemplateTotals($form);
    });
});


// =====================================================
// Pagination Info
// =====================================================

function updateTlInfo() {

    if (!dataTableInstance) {
        return;
    }

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

    DataTableSalaryTemplates.init();

    $('#salaryTemplateSearch').on('keyup', function () {
        if (!dataTableInstance) return;
        dataTableInstance.draw();
    });

    $('#tlPrev').on('click', function () {
        if (!dataTableInstance) return;
        dataTableInstance.page('previous').draw('page');
    });

    $('#tlNext').on('click', function () {
        if (!dataTableInstance) return;
        dataTableInstance.page('next').draw('page');
    });

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
        if (!dataTableInstance) return;
        dataTableInstance.draw();
    });

    $('#salaryTemplatePayTypeFilter').on('change', function () {
        if (!dataTableInstance) return;
        dataTableInstance.draw();
    });
});
