var dataTableInstance;

var DataTableEmployees = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#employeeTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#employeeTable').data('url'),
                data: function (d) {
                    d.search = $('#employeeSearch').val();

                    applyEmployeeAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
                { data: 'contact' },
                { data: 'type_status' },
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
                        <p class="text-muted mb-0">No employees available</p>
                    </div>
                `
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateTlInfo();
                _componentSwitch();
                if (typeof _componentRemoteOffcanvasLoadAfterAjax === 'function') {
                    _componentRemoteOffcanvasLoadAfterAjax();
                }
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
            initEmployeeAdvSearch();
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
    DataTableEmployees.init();

    // Search
    $('#employeeSearch').on('keyup', function () {
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

var employeeAdvFilters = {};

function initEmployeeAdvSearch() {

    if (!$('#employeeAdvSearchModal').length) {
        return;
    }

    $('#advDepartment, #advDesignation').select2({
        width: '100%',
        dropdownParent: $('#employeeAdvSearchModal')
    });

    $('.as-select').select2({
        width: '100%',
        dropdownParent: $('#employeeAdvSearchModal')
    });

    $('#advSearchApply').on('click', function () {
        applyEmployeeAdvFilters(true);
    });

    $('#advSearchReset').on('click', function () {
        resetEmployeeAdvFieldsUI();
    });

    $(document).on('click', '.adv-chip-remove', function () {

        var key = $(this).data('key');

        delete employeeAdvFilters[key];

        clearEmployeeAdvField(key);

        renderEmployeeFilterChips();

        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });

    $(document).on('click', '#advClearAllChips', function () {

        employeeAdvFilters = {};

        resetEmployeeAdvFieldsUI();

        renderEmployeeFilterChips();

        if (dataTableInstance) {
            dataTableInstance.draw();
        }
    });
}

function clearEmployeeAdvField(key) {

    switch (key) {

        case 'employee_type_id':
            $('#advEmployeeType').val('').trigger('change.select2');
            break;

        case 'employment_status_id':
            $('#advEmploymentStatus').val('').trigger('change.select2');
            break;

        case 'shift_id':
            $('#advShift').val('').trigger('change.select2');
            break;

        case 'status':
            $('#advRecordStatus').val('').trigger('change.select2');
            break;

        case 'department_id':
            $('#advDepartment').val('').trigger('change.select2');
            break;

        case 'designation_id':
            $('#advDesignation').val('').trigger('change.select2');
            break;

        case 'gender':
            $('#advGender').val('').trigger('change.select2');
            break;

        case 'joining_date':
            $('#advJoiningFrom, #advJoiningTo').val('');
            break;

        case 'birth_date':
            $('#advBirthFrom, #advBirthTo').val('');
            break;
    }
}

function resetEmployeeAdvFieldsUI() {

    $('#advEmployeeType, #advEmploymentStatus, #advShift, #advRecordStatus, #advDepartment, #advDesignation, #advGender')
        .val('')
        .trigger('change.select2');

    $('#advJoiningFrom, #advJoiningTo, #advBirthFrom, #advBirthTo').val('');
}

function collectEmployeeAdvFilters() {

    var filters = {};

    var $employeeType = $('#advEmployeeType');
    if ($employeeType.val()) {
        filters.employee_type_id = {
            value: $employeeType.val(),
            label: 'Type: ' + $employeeType.find('option:selected').text()
        };
    }

    var $employmentStatus = $('#advEmploymentStatus');
    if ($employmentStatus.val()) {
        filters.employment_status_id = {
            value: $employmentStatus.val(),
            label: 'Employment Status: ' + $employmentStatus.find('option:selected').text()
        };
    }

    var $shift = $('#advShift');
    if ($shift.val()) {
        filters.shift_id = {
            value: $shift.val(),
            label: 'Shift: ' + $shift.find('option:selected').text()
        };
    }

    var $recordStatus = $('#advRecordStatus');
    if ($recordStatus.val() !== '') {
        filters.status = {
            value: $recordStatus.val(),
            label: 'Record Status: ' + $recordStatus.find('option:selected').text()
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

    var $gender = $('#advGender');
    if ($gender.val()) {
        filters.gender = {
            value: $gender.val(),
            label: 'Gender: ' + $gender.find('option:selected').text()
        };
    }

    var joiningFrom = $('#advJoiningFrom').val();
    var joiningTo = $('#advJoiningTo').val();
    if (joiningFrom || joiningTo) {
        filters.joining_date = {
            value: { from: joiningFrom, to: joiningTo },
            label: 'Joining: ' + (joiningFrom || '…') + ' → ' + (joiningTo || '…')
        };
    }

    var birthFrom = $('#advBirthFrom').val();
    var birthTo = $('#advBirthTo').val();
    if (birthFrom || birthTo) {
        filters.birth_date = {
            value: { from: birthFrom, to: birthTo },
            label: 'Birth: ' + (birthFrom || '…') + ' → ' + (birthTo || '…')
        };
    }

    return filters;
}

function renderEmployeeFilterChips() {

    var $bar = $('#advSearchChipsBar');
    var $chips = $('#advSearchChips');
    var keys = Object.keys(employeeAdvFilters);

    $chips.empty();

    if (!keys.length) {
        $bar.hide();
        $('#advSearchBadge').hide();
        return;
    }

    keys.forEach(function (key) {

        var filter = employeeAdvFilters[key];

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

function applyEmployeeAdvFilters(closeModal) {

    employeeAdvFilters = collectEmployeeAdvFilters();

    renderEmployeeFilterChips();

    if (dataTableInstance) {
        dataTableInstance.draw();
    }

    if (closeModal && typeof bootstrap !== 'undefined') {

        var modalEl = document.getElementById('employeeAdvSearchModal');
        var instance = bootstrap.Modal.getInstance(modalEl);

        if (instance) {
            instance.hide();
        }
    }
}

function applyEmployeeAdvFiltersToRequest(d) {

    if (employeeAdvFilters.employee_type_id) {
        d.employee_type_id = employeeAdvFilters.employee_type_id.value;
    }

    if (employeeAdvFilters.employment_status_id) {
        d.employment_status_id = employeeAdvFilters.employment_status_id.value;
    }

    if (employeeAdvFilters.shift_id) {
        d.shift_id = employeeAdvFilters.shift_id.value;
    }

    if (employeeAdvFilters.status) {
        d.status = employeeAdvFilters.status.value;
    }

    if (employeeAdvFilters.department_id) {
        d.department_id = employeeAdvFilters.department_id.value;
    }

    if (employeeAdvFilters.designation_id) {
        d.designation_id = employeeAdvFilters.designation_id.value;
    }

    if (employeeAdvFilters.gender) {
        d.gender = employeeAdvFilters.gender.value;
    }

    if (employeeAdvFilters.joining_date) {

        if (employeeAdvFilters.joining_date.value.from) {
            d.joining_from = employeeAdvFilters.joining_date.value.from;
        }

        if (employeeAdvFilters.joining_date.value.to) {
            d.joining_to = employeeAdvFilters.joining_date.value.to;
        }
    }

    if (employeeAdvFilters.birth_date) {

        if (employeeAdvFilters.birth_date.value.from) {
            d.birth_from = employeeAdvFilters.birth_date.value.from;
        }

        if (employeeAdvFilters.birth_date.value.to) {
            d.birth_to = employeeAdvFilters.birth_date.value.to;
        }
    }
}
