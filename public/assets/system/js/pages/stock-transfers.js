var dataTableInstance;
var stockTransferItemRowIndex = 0;

var DataTableStockTransfers = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#stockTransferTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#stockTransferTable').data('url'),
                data: function (d) {
                    d.search = $('#stockTransferSearch').val();
                    d.warehouse_id = $('#warehouseFilter').val();
                    d.transfer_status = $('#transferStatusFilter').val();
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
                { data: 'transfer_number' },
                { data: 'items_count_label' },
                { data: 'transfer_date_formatted' },
                { data: 'transfer_status_badge' },
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
                        <p class="text-muted mb-0">No stock transfers available</p>
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
// Stock Transfer Item Row Builder
// =====================================================
function buildStockTransferItemRow(container, itemData) {
    var form = container.closest('form');
    var index = stockTransferItemRowIndex++;
    var productOptionsHtml = form.find('.stock-transfer-product-options').html();

    var row = $(`
        <div class="fm-grid stock-transfer-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select stt-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control stt-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control stt-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-stock-transfer-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.stt-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.stt-item-product').val(itemData.product_id);
        row.find('.stt-item-quantity').val(itemData.quantity);
        row.find('.stt-item-notes').val(itemData.notes);
    }

    container.append(row);
}

$(document).on('click', '.stock-transfer-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.stock-transfer-item-rows');
    buildStockTransferItemRow(container);
});

$(document).on('click', '.remove-stock-transfer-item', function () {
    $(this).closest('.stock-transfer-item-row').remove();
});

function populateExistingStockTransferItems(scope) {
    $(scope).find('.stock-transfer-item-rows[data-existing]').each(function () {
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
            buildStockTransferItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingStockTransferItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableStockTransfers.init();

    // Search
    $('#stockTransferSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Warehouse filter
    $('#warehouseFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Transfer status filter
    $('#transferStatusFilter').on('change', function () {
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
