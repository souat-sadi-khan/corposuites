var dataTableInstance;
var stockEntryItemRowIndex = 0;

var DataTableStockEntries = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#stockEntryTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#stockEntryTable').data('url'),
                data: function (d) {
                    d.search = $('#stockEntrySearch').val();
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
                { data: 'entry_date_formatted' },
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
                        <img src="${window.location.origin}/assets/images/nothing-to-show.svg" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No stock entries available</p>
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
// Stock Entry Item Row Builder
// =====================================================
function buildStockEntryItemRow(container, itemData) {
    var form = container.closest('form');
    var index = stockEntryItemRowIndex++;
    var productOptionsHtml = form.find('.stock-entry-product-options').html();

    var row = $(`
        <div class="fm-grid stock-entry-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select se-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control se-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control se-item-cost" name="items[${index}][unit_cost]" placeholder="Unit Cost">
            </div>
            <div class="fm-field">
                <input type="text" class="form-control se-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-stock-entry-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.se-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.se-item-product').val(itemData.product_id);
        row.find('.se-item-quantity').val(itemData.quantity);
        row.find('.se-item-cost').val(itemData.unit_cost);
        row.find('.se-item-notes').val(itemData.notes);
    }

    container.append(row);
}

$(document).on('click', '.stock-entry-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.stock-entry-item-rows');
    buildStockEntryItemRow(container);
});

$(document).on('click', '.remove-stock-entry-item', function () {
    $(this).closest('.stock-entry-item-row').remove();
});

function populateExistingStockEntryItems(scope) {
    $(scope).find('.stock-entry-item-rows[data-existing]').each(function () {
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
            buildStockEntryItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingStockEntryItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableStockEntries.init();

    // Search
    $('#stockEntrySearch').on('keyup', function () {
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
