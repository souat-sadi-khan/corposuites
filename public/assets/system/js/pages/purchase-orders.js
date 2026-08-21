var dataTableInstance;
var purchaseOrderItemRowIndex = 0;

var DataTablePurchaseOrders = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#purchaseOrderTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#purchaseOrderTable').data('url'),
                data: function (d) {
                    d.search = $('#purchaseOrderSearch').val();
                    d.vendor_id = $('#vendorFilter').val();
                    d.order_status = $('#orderStatusFilter').val();
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
                { data: 'po_number' },
                { data: 'items_count_label' },
                { data: 'order_date_formatted' },
                { data: 'grand_total_formatted' },
                { data: 'order_status_badge' },
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
                        <p class="text-muted mb-0">No purchase orders available</p>
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
// Purchase Order Item Row Builder
// =====================================================
function buildPurchaseOrderItemRow(container, itemData) {
    var form = container.closest('form');
    var index = purchaseOrderItemRowIndex++;
    var productOptionsHtml = form.find('.purchase-order-product-options').html();

    var row = $(`
        <div class="fm-grid purchase-order-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select po-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control po-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control po-item-price" name="items[${index}][unit_price]" placeholder="Unit Price" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control po-item-discount" name="items[${index}][discount]" placeholder="Discount" value="0">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-purchase-order-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.po-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.po-item-product').val(itemData.product_id);
        row.find('.po-item-quantity').val(itemData.quantity);
        row.find('.po-item-price').val(itemData.unit_price);
        row.find('.po-item-discount').val(itemData.discount);
    }

    container.append(row);
    recalculatePurchaseOrderTotals(container);
}

$(document).on('click', '.purchase-order-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.purchase-order-item-rows');
    buildPurchaseOrderItemRow(container);
});

$(document).on('click', '.remove-purchase-order-item', function () {
    var container = $(this).closest('.purchase-order-item-rows');
    $(this).closest('.purchase-order-item-row').remove();
    recalculatePurchaseOrderTotals(container);
});

// Auto-fill unit price from the selected product's selling price
$(document).on('change', '.po-item-product', function () {
    var selected = $(this).find('option:selected');
    var price = selected.data('price');
    var row = $(this).closest('.purchase-order-item-row');
    if (price !== undefined && price !== '' && !row.find('.po-item-price').val()) {
        row.find('.po-item-price').val(price);
    }
    recalculatePurchaseOrderTotals($(this).closest('.purchase-order-item-rows'));
});

$(document).on('input', '.po-item-quantity, .po-item-price, .po-item-discount', function () {
    recalculatePurchaseOrderTotals($(this).closest('.purchase-order-item-rows'));
});

function recalculatePurchaseOrderTotals(container) {
    var form = container.closest('form');
    var subtotal = 0;
    var discountTotal = 0;

    container.find('.purchase-order-item-row').each(function () {
        var qty = parseFloat($(this).find('.po-item-quantity').val()) || 0;
        var price = parseFloat($(this).find('.po-item-price').val()) || 0;
        var discount = parseFloat($(this).find('.po-item-discount').val()) || 0;

        subtotal += qty * price;
        discountTotal += discount;
    });

    var grandTotal = subtotal - discountTotal;

    form.find('.po-subtotal-preview').text(subtotal.toFixed(2));
    form.find('.po-discount-preview').text(discountTotal.toFixed(2));
    form.find('.po-grandtotal-preview').text(grandTotal.toFixed(2));
}

function populateExistingPurchaseOrderItems(scope) {
    $(scope).find('.purchase-order-item-rows[data-existing]').each(function () {
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
            buildPurchaseOrderItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingPurchaseOrderItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTablePurchaseOrders.init();

    // Search
    $('#purchaseOrderSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Vendor filter
    $('#vendorFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Order status filter
    $('#orderStatusFilter').on('change', function () {
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
