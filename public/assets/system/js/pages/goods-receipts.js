var dataTableInstance;
var goodsReceiptItemRowIndex = 0;

var DataTableGoodsReceipts = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#goodsReceiptTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#goodsReceiptTable').data('url'),
                data: function (d) {
                    d.search = $('#goodsReceiptSearch').val();
                    d.purchase_order_id = $('#purchaseOrderFilter').val();
                    d.receipt_status = $('#receiptStatusFilter').val();
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
                { data: 'receipt_number' },
                { data: 'items_count_label' },
                { data: 'received_date_formatted' },
                { data: 'receipt_status_badge' },
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
                        <p class="text-muted mb-0">No goods receipts available</p>
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
// Goods Receipt Item Row Builder
// =====================================================
function buildGoodsReceiptItemRow(container, itemData) {
    var form = container.closest('form');
    var index = goodsReceiptItemRowIndex++;
    var productOptionsHtml = form.find('.goods-receipt-product-options').html();

    var row = $(`
        <div class="fm-grid goods-receipt-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select gr-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:120px;">
                <input type="number" step="0.01" min="0.01" class="form-control gr-item-quantity" name="items[${index}][quantity_received]" placeholder="Qty Received" value="1" required>
            </div>
            <div class="fm-field" style="max-width:140px;">
                <select class="form-select gr-item-condition" name="items[${index}][condition]">
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="defective">Defective</option>
                </select>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control gr-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-goods-receipt-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.gr-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.gr-item-product').val(itemData.product_id);
        row.find('.gr-item-quantity').val(itemData.quantity_received);
        row.find('.gr-item-condition').val(itemData.condition);
        row.find('.gr-item-notes').val(itemData.notes);
    }

    container.append(row);
}

$(document).on('click', '.goods-receipt-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.goods-receipt-item-rows');
    buildGoodsReceiptItemRow(container);
});

$(document).on('click', '.remove-goods-receipt-item', function () {
    $(this).closest('.goods-receipt-item-row').remove();
});

function populateExistingGoodsReceiptItems(scope) {
    $(scope).find('.goods-receipt-item-rows[data-existing]').each(function () {
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
            buildGoodsReceiptItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingGoodsReceiptItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableGoodsReceipts.init();

    // Search
    $('#goodsReceiptSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Purchase order filter
    $('#purchaseOrderFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Receipt status filter
    $('#receiptStatusFilter').on('change', function () {
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
