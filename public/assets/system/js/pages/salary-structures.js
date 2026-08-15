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
                    var employeeId = $('#salaryStructureTable').data('employee-id');
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
                { data: 'effective_date_formatted' },
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
                        <img src="${window.location.origin}/assets/images/nothing-to-show.png" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No salary structures available</p>
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
// Dynamic Salary Component Rows (event-delegated so it
// works regardless of when the modal form is injected)
// =====================================================
var salaryComponentRowIndex = 0;

function buildSalaryComponentRow(container, componentId, amount) {
    var optionsHtml = container.closest('form').find('.salary-component-options').html();
    var index = salaryComponentRowIndex++;

    var row = $(`
        <div class="fm-grid salary-component-row mb-2">
            <div class="fm-field">
                <select name="components[${index}][salary_component_id]" class="form-select">${optionsHtml}</select>
            </div>
            <div class="fm-field">
                <input type="number" step="0.01" min="0" class="form-control" name="components[${index}][amount]" placeholder="Amount" value="0">
            </div>
            <div class="fm-field">
                <button type="button" class="btn-nx-outline btn-sm remove-salary-component">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    if (componentId) {
        row.find('select').val(componentId);
    }
    if (amount !== undefined) {
        row.find('input').val(amount);
    }

    container.append(row);
}

$(document).on('click', '.salary-component-add', function () {
    var container = $(this).closest('.modal-body, .offcanvas-body').find('.salary-component-rows');
    buildSalaryComponentRow(container);
});

$(document).on('click', '.remove-salary-component', function () {
    $(this).closest('.salary-component-row').remove();
});

// Populate existing components when the edit form is injected into the modal
function populateExistingSalaryComponents(scope) {
    $(scope).find('.salary-component-rows[data-existing]').each(function () {
        var container = $(this);
        if (container.data('populated')) return;
        container.data('populated', true);

        var existing = [];
        try {
            existing = JSON.parse(container.attr('data-existing')) || [];
        } catch (e) {
            existing = [];
        }

        existing.forEach(function (item) {
            buildSalaryComponentRow(container, item.salary_component_id, item.amount);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingSalaryComponents(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

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
    DataTableSalaryStructures.init();

    // Search
    $('#salaryStructureSearch').on('keyup', function () {
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
