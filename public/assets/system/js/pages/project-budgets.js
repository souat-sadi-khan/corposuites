var dataTableInstance;
var projectBudgetItemRowIndex = 0;

var DataTableProjectBudgets = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#projectBudgetTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#projectBudgetTable').data('url'),
                data: function (d) {
                    d.search = $('#projectBudgetSearch').val();
                    d.project_id = $('#projectFilter').val();
                    d.budget_status = $('#budgetStatusFilter').val();
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
                { data: 'budget_col' },
                { data: 'project_name' },
                { data: 'items_count_label' },
                { data: 'budget_date_formatted' },
                { data: 'total_formatted' },
                { data: 'budget_status_badge' },
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
                        <p class="text-muted mb-0">No project budgets available</p>
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
// Budget Line Row Builder
// =====================================================
function buildProjectBudgetItemRow(container, itemData) {
    var form = container.closest('form');
    var index = projectBudgetItemRowIndex++;
    var categoryOptionsHtml = form.find('.project-budget-category-options').html();

    var row = $(`
        <div class="fm-grid project-budget-item-row mb-2" data-item-index="${index}">
            <div class="fm-field" style="max-width:160px;">
                <select class="form-select project-budget-item-category" name="items[${index}][category]" required></select>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control project-budget-item-description" name="items[${index}][description]" placeholder="Description">
            </div>
            <div class="fm-field" style="max-width:150px;">
                <input type="number" step="0.01" min="0" class="form-control project-budget-item-amount" name="items[${index}][amount]" placeholder="Amount" required>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-project-budget-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.project-budget-item-category').html(categoryOptionsHtml);

    if (itemData) {
        row.find('.project-budget-item-category').val(itemData.category);
        row.find('.project-budget-item-description').val(itemData.description || '');
        row.find('.project-budget-item-amount').val(itemData.amount);
    }

    container.append(row);
    recalculateProjectBudgetTotal(container);
}

// =====================================================
// Live total preview — a convenience only; the authoritative
// total is summed server-side by ProjectBudgetService.
// =====================================================
function recalculateProjectBudgetTotal(container) {
    var form = $(container).closest('form');
    var total = 0;

    form.find('.project-budget-item-amount').each(function () {
        var amount = parseFloat($(this).val());
        if (!isNaN(amount)) {
            total += amount;
        }
    });

    form.find('.pbg-total-preview').text(total.toFixed(2));
}

$(document).on('click', '.project-budget-item-add', function () {
    var form = $(this).closest('form');
    buildProjectBudgetItemRow(form.find('.project-budget-item-rows'));
});

$(document).on('click', '.remove-project-budget-item', function () {
    var container = $(this).closest('.project-budget-item-rows');
    $(this).closest('.project-budget-item-row').remove();
    recalculateProjectBudgetTotal(container);
});

$(document).on('input', '.project-budget-item-amount', function () {
    recalculateProjectBudgetTotal($(this).closest('.project-budget-item-rows'));
});

function populateExistingProjectBudgetItems(scope) {
    $(scope).find('.project-budget-item-rows[data-existing]').each(function () {
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
            buildProjectBudgetItemRow(container, item);
        });
    });
}

// =====================================================
// Approval fields only apply to an approved budget — mirrors
// what the service derives server-side.
// =====================================================
function toggleBudgetApprovalFields(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $select = $scope.find('.budget-status-select');

    if (!$select.length) {
        return;
    }

    var $fields = $scope.find('.budget-approval-field');

    if ($select.val() === 'approved') {
        $fields.show();
    } else {
        $fields.hide();
    }
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingProjectBudgetItems(modalContent);
        toggleBudgetApprovalFields('#modal_remote .modal-content');
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectBudgets.init();

    // Search
    $('#projectBudgetSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#projectFilter, #budgetStatusFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional approval fields inside the remote modal
    $(document).on('change', '.budget-status-select', function () {
        toggleBudgetApprovalFields($(this).closest('form'));
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
