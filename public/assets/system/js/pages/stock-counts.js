var dataTableInstance;
var stockCountItemRowIndex = 0;

var DataTableStockCounts = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#stockCountTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#stockCountTable').data('url'),
                data: function (d) {
                    d.search = $('#stockCountSearch').val();
                    d.warehouse_id = $('#warehouseFilter').val();
                    d.count_status = $('#countStatusFilter').val();
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
                { data: 'count_number' },
                { data: 'items_count_label' },
                { data: 'count_date_formatted' },
                { data: 'count_status_badge' },
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
                        <p class="text-muted mb-0">No stock counts available</p>
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
// Stock Count Item Row Builder
// =====================================================
function buildStockCountItemRow(container, itemData) {
    var form = container.closest('form');
    var index = stockCountItemRowIndex++;
    var productOptionsHtml = form.find('.stock-count-product-options').html();

    var row = $(`
        <div class="fm-grid stock-count-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select sc-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:120px;">
                <input type="number" step="0.01" min="0" class="form-control sc-item-system" name="items[${index}][system_quantity]" placeholder="System Qty">
            </div>
            <div class="fm-field" style="max-width:120px;">
                <input type="number" step="0.01" min="0" class="form-control sc-item-counted" name="items[${index}][counted_quantity]" placeholder="Counted Qty" value="0" required>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <label class="mb-1">Variance</label>
                <div class="form-control-plaintext sc-item-variance">-</div>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control sc-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-stock-count-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.sc-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.sc-item-product').val(itemData.product_id);
        row.find('.sc-item-system').val(itemData.system_quantity);
        row.find('.sc-item-counted').val(itemData.counted_quantity);
        row.find('.sc-item-notes').val(itemData.notes);
    }

    container.append(row);
    recalculateStockCountVariance(row);
}

$(document).on('click', '.stock-count-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.stock-count-item-rows');
    buildStockCountItemRow(container);
});

$(document).on('click', '.remove-stock-count-item', function () {
    $(this).closest('.stock-count-item-row').remove();
});

$(document).on('input', '.sc-item-system, .sc-item-counted', function () {
    recalculateStockCountVariance($(this).closest('.stock-count-item-row'));
});

function recalculateStockCountVariance(row) {
    var systemVal = row.find('.sc-item-system').val();
    var countedVal = parseFloat(row.find('.sc-item-counted').val()) || 0;

    if (systemVal === '' || systemVal === null || systemVal === undefined) {
        row.find('.sc-item-variance').text('-');
        return;
    }

    var variance = countedVal - parseFloat(systemVal);
    row.find('.sc-item-variance').text((variance > 0 ? '+' : '') + variance.toFixed(2));
}

function populateExistingStockCountItems(scope) {
    $(scope).find('.stock-count-item-rows[data-existing]').each(function () {
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
            buildStockCountItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingStockCountItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableStockCounts.init();

    // Search
    $('#stockCountSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Warehouse filter
    $('#warehouseFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Count status filter
    $('#countStatusFilter').on('change', function () {
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
