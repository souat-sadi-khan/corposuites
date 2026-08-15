var dataTableInstance;
var openingStockItemRowIndex = 0;

var DataTableOpeningStocks = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#openingStockTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#openingStockTable').data('url'),
                data: function (d) {
                    d.search = $('#openingStockSearch').val();
                    d.warehouse_id = $('#warehouseFilter').val();
                    d.entry_status = $('#entryStatusFilter').val();
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
                { data: 'entry_number' },
                { data: 'items_count_label' },
                { data: 'opening_date_formatted' },
                { data: 'total_value_formatted' },
                { data: 'entry_status_badge' },
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
                        <p class="text-muted mb-0">No opening stock entries available</p>
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
// Opening Stock Item Row Builder
// =====================================================
function buildOpeningStockItemRow(container, itemData) {
    var form = container.closest('form');
    var index = openingStockItemRowIndex++;
    var productOptionsHtml = form.find('.opening-stock-product-options').html();

    var row = $(`
        <div class="fm-grid opening-stock-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select os-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control os-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control os-item-cost" name="items[${index}][unit_cost]" placeholder="Unit Cost" required>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control os-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-opening-stock-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.os-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.os-item-product').val(itemData.product_id);
        row.find('.os-item-quantity').val(itemData.quantity);
        row.find('.os-item-cost').val(itemData.unit_cost);
        row.find('.os-item-notes').val(itemData.notes);
    }

    container.append(row);
    recalculateOpeningStockTotal(container);
}

$(document).on('click', '.opening-stock-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.opening-stock-item-rows');
    buildOpeningStockItemRow(container);
});

$(document).on('click', '.remove-opening-stock-item', function () {
    var container = $(this).closest('.opening-stock-item-rows');
    $(this).closest('.opening-stock-item-row').remove();
    recalculateOpeningStockTotal(container);
});

// Auto-fill unit cost from the selected product's cost price
$(document).on('change', '.os-item-product', function () {
    var selected = $(this).find('option:selected');
    var price = selected.data('price');
    var row = $(this).closest('.opening-stock-item-row');
    if (price !== undefined && price !== '' && !row.find('.os-item-cost').val()) {
        row.find('.os-item-cost').val(price);
    }
    recalculateOpeningStockTotal($(this).closest('.opening-stock-item-rows'));
});

$(document).on('input', '.os-item-quantity, .os-item-cost', function () {
    recalculateOpeningStockTotal($(this).closest('.opening-stock-item-rows'));
});

function recalculateOpeningStockTotal(container) {
    var form = container.closest('form');
    var total = 0;

    container.find('.opening-stock-item-row').each(function () {
        var qty = parseFloat($(this).find('.os-item-quantity').val()) || 0;
        var cost = parseFloat($(this).find('.os-item-cost').val()) || 0;

        total += qty * cost;
    });

    form.find('.os-total-preview').text(total.toFixed(2));
}

function populateExistingOpeningStockItems(scope) {
    $(scope).find('.opening-stock-item-rows[data-existing]').each(function () {
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
            buildOpeningStockItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingOpeningStockItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableOpeningStocks.init();

    // Search
    $('#openingStockSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Warehouse filter
    $('#warehouseFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Entry status filter
    $('#entryStatusFilter').on('change', function () {
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
