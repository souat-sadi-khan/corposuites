var dataTableInstance;
var budgetItemRowIndex = 0;

var DataTableBudgets = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#budgetTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#budgetTable').data('url'),
                data: function (d) {
                    d.search = $('#budgetSearch').val();
                    d.period_type = $('#periodTypeFilter').val();
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
                { data: 'period_col' },
                { data: 'items_count_label' },
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
                        <img src="${window.location.origin}/assets/images/nothing-to-show.svg" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No budgets available</p>
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
function buildBudgetItemRow(container, itemData) {
    var form = container.closest('form');
    var index = budgetItemRowIndex++;
    var accountOptionsHtml = form.find('.budget-account-options').html();

    var row = $(`
        <div class="fm-grid budget-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select budget-item-account" name="items[${index}][chart_of_account_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:150px;">
                <input type="number" step="0.01" min="0" class="form-control budget-item-amount" name="items[${index}][planned_amount]" placeholder="Planned Amount" required>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control budget-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-budget-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.budget-item-account').html('<option value="">Select account</option>' + accountOptionsHtml);

    if (itemData) {
        row.find('.budget-item-account').val(itemData.chart_of_account_id);
        row.find('.budget-item-amount').val(itemData.planned_amount);
        row.find('.budget-item-notes').val(itemData.notes || '');
    }

    container.append(row);
    recalculateBudgetTotal(container);
}

// =====================================================
// Live total preview — a convenience only; the authoritative
// total is summed server-side by BudgetService.
// =====================================================
function recalculateBudgetTotal(container) {
    var form = $(container).closest('form');
    var total = 0;

    form.find('.budget-item-amount').each(function () {
        var amount = parseFloat($(this).val());
        if (!isNaN(amount)) {
            total += amount;
        }
    });

    form.find('.bud-total-preview').text(total.toFixed(2));
}

$(document).on('click', '.budget-item-add', function () {
    var form = $(this).closest('form');
    buildBudgetItemRow(form.find('.budget-item-rows'));
});

$(document).on('click', '.remove-budget-item', function () {
    var container = $(this).closest('.budget-item-rows');
    $(this).closest('.budget-item-row').remove();
    recalculateBudgetTotal(container);
});

$(document).on('input', '.budget-item-amount', function () {
    recalculateBudgetTotal($(this).closest('.budget-item-rows'));
});

function populateExistingBudgetItems(scope) {
    $(scope).find('.budget-item-rows[data-existing]').each(function () {
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
            buildBudgetItemRow(container, item);
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
        populateExistingBudgetItems(modalContent);
        toggleBudgetApprovalFields('#modal_remote .modal-content');
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableBudgets.init();

    // Search
    $('#budgetSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#periodTypeFilter, #budgetStatusFilter').on('change', function () {
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
