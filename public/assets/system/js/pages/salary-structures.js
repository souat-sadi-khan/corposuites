var dataTableInstance;

var DataTableSalaryStructures = function () {

    var initDataTable = function () {

        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#salaryStructureTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],

            ajax: {
                url: $('#salaryStructureTable').data('url'),

                data: function (d) {

                    d.search = $('#salaryStructureSearch').val();

                    var employeeId = $('#salaryStructureTable')
                        .data('employee-id');

                    if (employeeId) {
                        d.employee_id = employeeId;
                    }

                    applySalaryStructureAdvFiltersToRequest(d);
                }
            },

            columns: [
                {
                    data: 'id',
                    visible: false
                },
                {
                    data: 'employee_name'
                },
                {
                    data: 'pay_type_badge'
                },
                {
                    data: 'effective_date_formatted'
                },
                {
                    data: 'salary_summary'
                },
                {
                    data: 'status_badge'
                },
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
                            No salary structures available
                        </p>
                    </div>
                `
            },

            drawCallback: function () {

                $('[data-toggle="tooltip"]').tooltip();

                updateTlInfo();

                _componentSwitch();

                if (
                    typeof _componentRemoteModalLoadAfterAjax ===
                    'function'
                ) {
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

var SALARY_PAY_TYPE_LABELS = {
    monthly: {
        label: 'Basic Salary',
        help: 'The employee\'s fixed monthly basic salary.'
    },
    daily: {
        label: 'Daily Rate',
        help: 'Rate paid per day worked. Net pay is this rate × days actually worked in the payroll period.'
    },
    commission: {
        label: 'Commission Rate (%)',
        help: 'Percentage applied against the sales amount entered when generating this employee\'s payroll.'
    }
};

function updateSalaryPayTypeUi(form) {

    var $form = $(form);

    var payType = $form
        .find('[name="pay_type"]')
        .val() || 'monthly';

    var config = SALARY_PAY_TYPE_LABELS[payType] || SALARY_PAY_TYPE_LABELS.monthly;

    $form.find('.salary-basic-label').contents().first().replaceWith(config.label + ' ');
    $form.find('.salary-basic-help').text(config.help);

    var $basicInput = $form.find('.salary-basic-input');

    if (payType === 'commission') {
        $basicInput.attr('max', 100);
    } else {
        $basicInput.removeAttr('max');
    }
}

$(document).on('change', '.salary-pay-type', function () {
    updateSalaryPayTypeUi($(this).closest('form'));
});


// =====================================================
// Salary Component Row Index
// =====================================================

var salaryComponentRowIndex = 0;


// =====================================================
// Build Salary Component Row
// =====================================================

function buildSalaryComponentRow(
    container,
    componentId,
    amount
) {

    var $container = $(container);

    var $form = $container.closest('form');

    var optionsHtml = $form
        .find('.salary-component-options')
        .html();

    var index = salaryComponentRowIndex++;

    var row = $(`
        <div class="salary-component-row mb-2">

            <div class="fm-field">

                <select
                    name="components[${index}][salary_component_id]"
                    class="form-select salary-select"
                    data-placeholder="Select Component">

                    ${optionsHtml}

                </select>

            </div>

            <div class="fm-field">

                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control salary-component-amount"
                    name="components[${index}][amount]"
                    placeholder="Amount"
                    value="0">

            </div>

            <div class="fm-field salary-component-action">

                <button
                    type="button"
                    class="btn-nx-outline btn-sm remove-salary-component">

                    <i class="ri-delete-bin-line"></i>

                </button>

            </div>

        </div>
    `);

    $container.append(row);

    var $select = row.find('.salary-select');

    var $amount = row.find('.salary-component-amount');


    // Existing component
    if (componentId) {

        $select.val(componentId);

    }


    // Existing amount
    if (
        amount !== undefined &&
        amount !== null
    ) {

        $amount
            .val(amount)
            .data('manual-value', true);

    }


    // Initialize Select2
    initSalaryComponentSelect(row);


    // Existing component calculation
    if (componentId) {

        updateSalaryComponentOptions($container);

    }


    // Update total
    calculateSalaryTotals($form);
}


// =====================================================
// Initialize Salary Component Select2
// =====================================================

function initSalaryComponentSelect(row) {

    var $select = $(row).find('.salary-select');

    if ($select.hasClass('select2-hidden-accessible')) {
        return;
    }

    $select.select2({
        dropdownParent: $('#modal_remote'),
        templateResult: _selectOptionTemplateSalaryStructure,
        templateSelection: function (option) {
            return option.text;
        }
    });
}


// =====================================================
// Salary Component Select2 Template
// =====================================================

function _selectOptionTemplateSalaryStructure(option) {

    if (!option.id || !option.element) {
        return option.text;
    }

    var $option = $(option.element);

    var desc = $option.attr('data-desc');

    var type = $option.attr('data-type');

    var value = parseFloat(
        $option.attr('data-value')
    ) || 0;

    var calculationType = $option.attr(
        'data-calculation-type'
    );

    var isEarning = type === 'earning';

    var typeClass = isEarning
        ? 'text-success'
        : 'text-danger';

    var typeBgClass = isEarning
        ? 'bg-success-subtle'
        : 'bg-danger-subtle';


    var $opt = $('<div class="sel-opt-rich"></div>');

    var $info = $('<div class="sel-opt-rich-info"></div>');

    var $nameRow = $('<div class="sel-opt-rich-name-row"></div>');


    // Component Name
    $nameRow.append(
        $('<div class="sel-opt-rich-name"></div>')
            .text(option.text)
    );


    // Earning / Deduction
    $nameRow.append(
        $('<span class="sel-opt-type-badge"></span>')
            .addClass(typeClass)
            .addClass(typeBgClass)
            .text(
                isEarning
                    ? 'Earning'
                    : 'Deduction'
            )
    );


    // Fixed / Percentage value
    var valueLabel = '';

    if (calculationType === 'percentage') {

        valueLabel = value + '%';

    } else {

        valueLabel = 'Fixed ' + value.toFixed(2);

    }


    $nameRow.append(
        $('<span class="sel-opt-percentage"></span>')
            .text(valueLabel)
    );


    $info.append($nameRow);


    // Description
    if (desc) {

        $info.append(
            $('<div class="sel-opt-rich-desc"></div>')
                .text(desc)
        );

    }


    $opt.append($info);

    return $opt;
}


// =====================================================
// Add Salary Component
// =====================================================

$(document).on(
    'click',
    '.salary-component-add',
    function () {

        var $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }


        var container = $button
            .closest('.modal-body, .offcanvas-body')
            .find('.salary-component-rows');


        if (!container.length) {
            return;
        }


        buildSalaryComponentRow(container);

    }
);


// =====================================================
// Component Change
// =====================================================

$(document).on(
    'change',
    '.salary-select',
    function () {

        var $select = $(this);

        var $row = $select.closest(
            '.salary-component-row'
        );

        var $container = $row.closest(
            '.salary-component-rows'
        );

        var $form = $select.closest('form');

        var componentId = $select.val();


        // Empty selection
        if (!componentId) {

            $row
                .find('.salary-component-amount')
                .val(0)
                .removeData('manual-value');

            updateSalaryComponentOptions($container);

            calculateSalaryTotals($form);

            return;
        }


        // =================================================
        // Duplicate Component Check
        // =================================================

        var duplicate = false;

        $container
            .find('.salary-select')
            .not($select)
            .each(function () {

                if ($(this).val() == componentId) {

                    duplicate = true;

                    return false;
                }

            });


        if (duplicate) {

            alert(
                'This salary component has already been selected.'
            );

            $select
                .val('')
                .trigger('change.select2');


            $row
                .find('.salary-component-amount')
                .val(0)
                .removeData('manual-value');


            updateSalaryComponentOptions($container);

            calculateSalaryTotals($form);

            return;
        }


        // =================================================
        // Component Data
        // =================================================

        var $option = $select.find(
            'option:selected'
        );

        var value = parseFloat(
            $option.attr('data-value')
        ) || 0;

        var calculationType = $option.attr(
            'data-calculation-type'
        );


        // =================================================
        // Basic Salary
        // =================================================

        var basicSalary = parseFloat(
            $form
                .find('[name="basic_salary"]')
                .val()
        ) || 0;


        var amount = 0;


        // =================================================
        // Fixed Calculation
        // =================================================

        if (calculationType === 'fixed') {

            amount = value;

        }


        // =================================================
        // Percentage Calculation
        // =================================================

        else if (
            calculationType === 'percentage'
        ) {

            amount =
                (basicSalary * value) / 100;

        }


        // =================================================
        // Set Amount
        // =================================================

        $row
            .find('.salary-component-amount')
            .val(amount.toFixed(2))
            .removeData('manual-value');


        // Update disabled options
        updateSalaryComponentOptions(
            $container
        );


        // Calculate total
        calculateSalaryTotals($form);

    }
);


// =====================================================
// Disable Already Selected Components
// =====================================================

function updateSalaryComponentOptions(container) {

    var $container = $(container);

    var selectedIds = [];


    $container
        .find('.salary-select')
        .each(function () {

            var value = $(this).val();

            if (value) {

                selectedIds.push(
                    String(value)
                );

            }

        });


    $container
        .find('.salary-select')
        .each(function () {

            var $select = $(this);

            var currentValue = String(
                $select.val() || ''
            );


            $select.find('option').each(
                function () {

                    var $option = $(this);

                    var optionValue = String(
                        $option.val() || ''
                    );


                    if (!optionValue) {
                        return;
                    }


                    // Own selected option remains enabled
                    if (
                        optionValue ===
                        currentValue
                    ) {

                        $option.prop(
                            'disabled',
                            false
                        );

                        return;
                    }


                    // Disable if already selected elsewhere
                    $option.prop(
                        'disabled',
                        selectedIds.includes(
                            optionValue
                        )
                    );

                }
            );


            // Refresh Select2
            if (
                $select.hasClass(
                    'select2-hidden-accessible'
                )
            ) {

                $select.trigger(
                    'change.select2'
                );

            }

        });
}


// =====================================================
// Remove Salary Component
// =====================================================

$(document).on(
    'click',
    '.remove-salary-component',
    function () {

        var $row = $(this).closest(
            '.salary-component-row'
        );

        var $container = $row.closest(
            '.salary-component-rows'
        );

        var $form = $row.closest('form');


        $row.remove();


        updateSalaryComponentOptions(
            $container
        );


        calculateSalaryTotals(
            $form
        );

    }
);


// =====================================================
// Manual Amount Change
// =====================================================

$(document).on(
    'input',
    '.salary-component-amount',
    function () {

        var $amount = $(this);

        // Mark as manually modified
        $amount.data(
            'manual-value',
            true
        );


        calculateSalaryTotals(
            $amount.closest('form')
        );

    }
);


// =====================================================
// Employee Change
// =====================================================

$(document).on(
    'change',
    '[name="employee_id"]',
    function () {

        var $form = $(this).closest('form');

        updateSalaryComponentAddButton(
            $form
        );

    }
);


// =====================================================
// Basic Salary Change
// =====================================================

$(document).on(
    'input',
    '[name="basic_salary"]',
    function () {

        var $form = $(this).closest('form');


        // Update Add Component button
        updateSalaryComponentAddButton(
            $form
        );


        // Recalculate percentage components
        recalculateSalaryComponents(
            $form
        );


        // Update total
        calculateSalaryTotals(
            $form
        );

    }
);


// =====================================================
// Add Component Button State
// =====================================================

function updateSalaryComponentAddButton(form) {

    var $form = $(form);

    var employeeId = $form
        .find('[name="employee_id"]')
        .val();


    var basicSalary = parseFloat(
        $form
            .find('[name="basic_salary"]')
            .val()
    ) || 0;


    var canAdd =
        employeeId &&
        basicSalary > 0;


    $form
        .find('.salary-component-add')
        .prop(
            'disabled',
            !canAdd
        );
}


// =====================================================
// Recalculate Existing Components
// =====================================================

function recalculateSalaryComponents(form) {

    var $form = $(form);

    var basicSalary = parseFloat(
        $form
            .find('[name="basic_salary"]')
            .val()
    ) || 0;


    $form
        .find('.salary-component-row')
        .each(function () {

            var $row = $(this);

            var $select = $row.find(
                '.salary-select'
            );

            var $amount = $row.find(
                '.salary-component-amount'
            );


            if (!$select.val()) {
                return;
            }


            // If user manually changed amount,
            // do not overwrite it.
            if (
                $amount.data(
                    'manual-value'
                ) === true
            ) {

                return;
            }


            var $option = $select.find(
                'option:selected'
            );


            var value = parseFloat(
                $option.attr('data-value')
            ) || 0;


            var calculationType =
                $option.attr(
                    'data-calculation-type'
                );


            var amount = 0;


            if (
                calculationType ===
                'fixed'
            ) {

                amount = value;

            }


            else if (
                calculationType ===
                'percentage'
            ) {

                amount =
                    (basicSalary * value) /
                    100;

            }


            $amount.val(
                amount.toFixed(2)
            );

        });
}


// =====================================================
// Calculate Salary Totals
// =====================================================

function calculateSalaryTotals(form) {

    var $form = $(form);

    if (!$form.length) {
        return;
    }


    // =================================================
    // Basic Salary
    // =================================================

    var basicSalary = parseFloat(
        $form
            .find('[name="basic_salary"]')
            .val()
    ) || 0;


    var totalEarnings = 0;

    var totalDeductions = 0;


    // =================================================
    // Components
    // =================================================

    $form
        .find('.salary-component-row')
        .each(function () {

            var $row = $(this);

            var $select = $row.find(
                '.salary-select'
            );


            if (!$select.val()) {
                return;
            }


            var amount = parseFloat(
                $row
                    .find(
                        '.salary-component-amount'
                    )
                    .val()
            ) || 0;


            var type = $select
                .find('option:selected')
                .attr('data-type');


            if (type === 'earning') {

                totalEarnings += amount;

            }


            else if (
                type === 'deduction'
            ) {

                totalDeductions += amount;

            }

        });


    // =================================================
    // Gross Salary
    // =================================================

    var grossSalary =
        basicSalary +
        totalEarnings -
        totalDeductions;


    // =================================================
    // Update UI
    // =================================================

    $form
        .find('.salary-basic-total')
        .text(
            formatSalaryAmount(
                basicSalary
            )
        );


    $form
        .find('.salary-earning-total')
        .text(
            formatSalaryAmount(
                totalEarnings
            )
        );


    $form
        .find('.salary-deduction-total')
        .text(
            formatSalaryAmount(
                totalDeductions
            )
        );


    $form
        .find('.salary-gross-total')
        .text(
            formatSalaryAmount(
                grossSalary
            )
        );
}


// =====================================================
// Format Salary Amount
// =====================================================

function formatSalaryAmount(amount) {

    return Number(
        amount || 0
    ).toLocaleString(
        'en-US',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );
}


// =====================================================
// Populate Existing Salary Components
// =====================================================

function populateExistingSalaryComponents(scope) {

    $(scope)
        .find(
            '.salary-component-rows[data-existing]'
        )
        .each(function () {

            var container = $(this);


            if (
                container.data(
                    'populated'
                )
            ) {

                return;
            }


            container.data(
                'populated',
                true
            );


            var existing = [];


            try {

                existing = JSON.parse(
                    container.attr(
                        'data-existing'
                    )
                ) || [];

            }

            catch (e) {

                existing = [];

            }


            existing.forEach(function (item) {

                buildSalaryComponentRow(
                    container,
                    item.salary_component_id,
                    item.amount
                );

            });


            var $form = container.closest(
                'form'
            );


            updateSalaryComponentOptions(
                container
            );


            updateSalaryComponentAddButton(
                $form
            );


            calculateSalaryTotals(
                $form
            );

        });
}


// =====================================================
// Observe Remote Modal
// =====================================================

(function observeModalContent() {

    var modalContent =
        document.querySelector(
            '#modal_remote .modal-content'
        );


    if (
        !modalContent ||
        typeof MutationObserver ===
        'undefined'
    ) {

        return;
    }


    new MutationObserver(
        function () {

            populateExistingSalaryComponents(
                modalContent
            );

        }
    ).observe(
        modalContent,
        {
            childList: true,
            subtree: true
        }
    );

})();


// =====================================================
// Modal Shown - Initial State
// =====================================================

$(document).on(
    'shown.bs.modal',
    '#modal_remote',
    function () {

        var $modal = $(this);


        $modal
            .find('form.ajax-form')
            .each(function () {

                var $form = $(this);


                // Basic Salary label/help text for the current pay type
                updateSalaryPayTypeUi(
                    $form
                );


                // Add button state
                updateSalaryComponentAddButton(
                    $form
                );


                // Existing component options
                $form
                    .find(
                        '.salary-component-rows'
                    )
                    .each(function () {

                        updateSalaryComponentOptions(
                            $(this)
                        );

                    });


                // Calculate total
                calculateSalaryTotals(
                    $form
                );

            });

    }
);


// =====================================================
// Pagination Info
// =====================================================

function updateTlInfo() {

    if (!dataTableInstance) {
        return;
    }


    var info =
        dataTableInstance
            .page
            .info();


    var start =
        info.recordsDisplay === 0
            ? 0
            : info.start + 1;


    $('#tlInfo').text(
        start +
        ' - ' +
        info.end +
        ' of ' +
        info.recordsDisplay
    );


    $('#tlPrev').prop(
        'disabled',
        info.page === 0
    );


    $('#tlNext').prop(
        'disabled',
        info.page >= info.pages - 1 ||
        info.pages === 0
    );
}


// =====================================================
// Document Ready
// =====================================================

document.addEventListener(
    'DOMContentLoaded',
    function () {

        DataTableSalaryStructures.init();


        // =================================================
        // Search
        // =================================================

        $('#salaryStructureSearch').on(
            'keyup',
            function () {

                if (!dataTableInstance) {
                    return;
                }

                dataTableInstance.draw();

            }
        );


        // =================================================
        // Previous
        // =================================================

        $('#tlPrev').on(
            'click',
            function () {

                if (!dataTableInstance) {
                    return;
                }

                dataTableInstance
                    .page('previous')
                    .draw('page');

            }
        );


        // =================================================
        // Next
        // =================================================

        $('#tlNext').on(
            'click',
            function () {

                if (!dataTableInstance) {
                    return;
                }

                dataTableInstance
                    .page('next')
                    .draw('page');

            }
        );


        // =================================================
        // Advanced Search
        // =================================================

        initSalaryStructureAdvSearch();

    }
);


// =====================================================
// Advanced Search — state, select2, chips, apply/reset
// =====================================================

var salaryStructureAdvFilters = {};

function initSalaryStructureAdvSearch() {

    if (!$('#salaryAdvSearchModal').length) {
        return;
    }

    $('#advEmployee, #advDepartment, #advDesignation').each(function () {

        $(this).select2({
            width: '100%',
            dropdownParent: $('#salaryAdvSearchModal'),
            templateResult: _selectOptionTemplate
        });

    });

    $('#advSearchApply').on('click', function () {
        applySalaryStructureAdvFilters(true);
    });

    $('#advSearchReset').on('click', function () {
        resetSalaryStructureAdvFieldsUI();
    });

    $(document).on('click', '.adv-chip-remove', function () {

        var key = $(this).data('key');

        delete salaryStructureAdvFilters[key];

        clearSalaryStructureAdvField(key);

        renderSalaryStructureFilterChips();

        if (dataTableInstance) {
            dataTableInstance.draw();
        }

    });

    $(document).on('click', '#advClearAllChips', function () {

        salaryStructureAdvFilters = {};

        resetSalaryStructureAdvFieldsUI();

        renderSalaryStructureFilterChips();

        if (dataTableInstance) {
            dataTableInstance.draw();
        }

    });
}

$('.as-select').select2({
    width: '100%',
    dropdownParent: $('#salaryAdvSearchModal')
});

function _selectOptionTemplate(option) {
    if (!option.id || !option.element) {
        return option.text;
    }

    var $option = $(option.element);

    var logo = $option.attr('data-logo');
    var desc = $option.attr('data-desc');

    var $opt = $('<div class="sel-opt-rich"></div>');

    // Avatar
    if (logo) {
        $opt.append(
            '<div class="sel-opt-rich-avatar">' +
                '<img class="sel-opt-rich-img" src="' + logo + '" alt="">' +
            '</div>'
        );
    }

    // Info
    var $info = $('<div class="sel-opt-rich-info"></div>');

    $info.append(
        $('<div class="sel-opt-rich-name"></div>').text(option.text)
    );

    if (desc) {
        $info.append(
            $('<div class="sel-opt-rich-desc"></div>').text(desc)
        );
    }

    $opt.append($info);

    return $opt;
}

function clearSalaryStructureAdvField(key) {

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

        case 'status':
            $('#advStatus').val('').trigger('change.select2');
            break;

        case 'effective_date':
            $('#advEffFrom, #advEffTo').val('');
            break;

        case 'salary_range':
            $('#advSalaryMin, #advSalaryMax').val('');
            break;
    }
}

function resetSalaryStructureAdvFieldsUI() {

    $('#advEmployee, #advDepartment, #advDesignation, #advPayType, #advStatus')
        .val('')
        .trigger('change.select2');

    $('#advEffFrom, #advEffTo, #advSalaryMin, #advSalaryMax').val('');
}

function collectSalaryStructureAdvFilters() {

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

    var $status = $('#advStatus');

    if ($status.val() !== '') {
        filters.status = {
            value: $status.val(),
            label: 'Status: ' + $status.find('option:selected').text()
        };
    }

    var effFrom = $('#advEffFrom').val();
    var effTo = $('#advEffTo').val();

    if (effFrom || effTo) {
        filters.effective_date = {
            value: { from: effFrom, to: effTo },
            label: 'Effective: ' + (effFrom || '…') + ' → ' + (effTo || '…')
        };
    }

    var salaryMin = $('#advSalaryMin').val();
    var salaryMax = $('#advSalaryMax').val();

    if (salaryMin !== '' || salaryMax !== '') {
        filters.salary_range = {
            value: { min: salaryMin, max: salaryMax },
            label: 'Salary: ' + (salaryMin !== '' ? salaryMin : '0') + ' - ' + (salaryMax !== '' ? salaryMax : '∞')
        };
    }

    return filters;
}

function renderSalaryStructureFilterChips() {

    var $bar = $('#advSearchChipsBar');
    var $chips = $('#advSearchChips');
    var keys = Object.keys(salaryStructureAdvFilters);

    $chips.empty();

    if (!keys.length) {
        $bar.hide();
        $('#advSearchBadge').hide();
        return;
    }

    keys.forEach(function (key) {

        var filter = salaryStructureAdvFilters[key];

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

function applySalaryStructureAdvFilters(closeModal) {

    salaryStructureAdvFilters = collectSalaryStructureAdvFilters();

    renderSalaryStructureFilterChips();

    if (dataTableInstance) {
        dataTableInstance.draw();
    }

    if (closeModal && typeof bootstrap !== 'undefined') {

        var modalEl = document.getElementById('salaryAdvSearchModal');
        var instance = bootstrap.Modal.getInstance(modalEl);

        if (instance) {
            instance.hide();
        }
    }
}

function applySalaryStructureAdvFiltersToRequest(d) {

    if (salaryStructureAdvFilters.employee_id) {
        d.employee_id = salaryStructureAdvFilters.employee_id.value;
    }

    if (salaryStructureAdvFilters.department_id) {
        d.department_id = salaryStructureAdvFilters.department_id.value;
    }

    if (salaryStructureAdvFilters.designation_id) {
        d.designation_id = salaryStructureAdvFilters.designation_id.value;
    }

    if (salaryStructureAdvFilters.pay_type) {
        d.pay_type = salaryStructureAdvFilters.pay_type.value;
    }

    if (salaryStructureAdvFilters.status) {
        d.status = salaryStructureAdvFilters.status.value;
    }

    if (salaryStructureAdvFilters.effective_date) {

        if (salaryStructureAdvFilters.effective_date.value.from) {
            d.effective_date_from = salaryStructureAdvFilters.effective_date.value.from;
        }

        if (salaryStructureAdvFilters.effective_date.value.to) {
            d.effective_date_to = salaryStructureAdvFilters.effective_date.value.to;
        }
    }

    if (salaryStructureAdvFilters.salary_range) {

        if (salaryStructureAdvFilters.salary_range.value.min !== '') {
            d.salary_min = salaryStructureAdvFilters.salary_range.value.min;
        }

        if (salaryStructureAdvFilters.salary_range.value.max !== '') {
            d.salary_max = salaryStructureAdvFilters.salary_range.value.max;
        }
    }
}
