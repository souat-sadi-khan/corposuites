var dataTableInstance;
var salesOrderItemRowIndex = 0;

var DataTableSalesOrders = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#salesOrderTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#salesOrderTable').data('url'),
                data: function (d) {
                    d.search = $('#salesOrderSearch').val();
                    d.customer_id = $('#customerFilter').val();
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
                { data: 'order_number' },
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
                        <p class="text-muted mb-0">No sales orders available</p>
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
// Sales Order Item Row Builder
// =====================================================
function buildSalesOrderItemRow(container, itemData) {
    var form = container.closest('form');
    var index = salesOrderItemRowIndex++;
    var productOptionsHtml = form.find('.sales-order-product-options').html();

    var row = $(`
        <div class="fm-grid sales-order-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select so-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control so-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control so-item-price" name="items[${index}][unit_price]" placeholder="Unit Price" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control so-item-discount" name="items[${index}][discount]" placeholder="Discount" value="0">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-sales-order-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.so-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.so-item-product').val(itemData.product_id);
        row.find('.so-item-quantity').val(itemData.quantity);
        row.find('.so-item-price').val(itemData.unit_price);
        row.find('.so-item-discount').val(itemData.discount);
    }

    container.append(row);
    recalculateSalesOrderTotals(container);
}

$(document).on('click', '.sales-order-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.sales-order-item-rows');
    buildSalesOrderItemRow(container);
});

$(document).on('click', '.remove-sales-order-item', function () {
    var container = $(this).closest('.sales-order-item-rows');
    $(this).closest('.sales-order-item-row').remove();
    recalculateSalesOrderTotals(container);
});

// Auto-fill unit price from the selected product's selling price
$(document).on('change', '.so-item-product', function () {
    var selected = $(this).find('option:selected');
    var price = selected.data('price');
    var row = $(this).closest('.sales-order-item-row');
    if (price !== undefined && price !== '' && !row.find('.so-item-price').val()) {
        row.find('.so-item-price').val(price);
    }
    recalculateSalesOrderTotals($(this).closest('.sales-order-item-rows'));
});

$(document).on('input', '.so-item-quantity, .so-item-price, .so-item-discount', function () {
    recalculateSalesOrderTotals($(this).closest('.sales-order-item-rows'));
});

function recalculateSalesOrderTotals(container) {
    var form = container.closest('form');
    var subtotal = 0;
    var discountTotal = 0;

    container.find('.sales-order-item-row').each(function () {
        var qty = parseFloat($(this).find('.so-item-quantity').val()) || 0;
        var price = parseFloat($(this).find('.so-item-price').val()) || 0;
        var discount = parseFloat($(this).find('.so-item-discount').val()) || 0;

        subtotal += qty * price;
        discountTotal += discount;
    });

    var grandTotal = subtotal - discountTotal;

    form.find('.so-subtotal-preview').text(subtotal.toFixed(2));
    form.find('.so-discount-preview').text(discountTotal.toFixed(2));
    form.find('.so-grandtotal-preview').text(grandTotal.toFixed(2));
}

function populateExistingSalesOrderItems(scope) {
    $(scope).find('.sales-order-item-rows[data-existing]').each(function () {
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
            buildSalesOrderItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingSalesOrderItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableSalesOrders.init();

    // Search
    $('#salesOrderSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Customer filter
    $('#customerFilter').on('change', function () {
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
