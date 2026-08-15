var dataTableInstance;
var purchaseInvoiceItemRowIndex = 0;

var DataTablePurchaseInvoices = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#purchaseInvoiceTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#purchaseInvoiceTable').data('url'),
                data: function (d) {
                    d.search = $('#purchaseInvoiceSearch').val();
                    d.vendor_id = $('#vendorFilter').val();
                    d.match_status = $('#matchStatusFilter').val();
                    d.invoice_status = $('#invoiceStatusFilter').val();
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
                { data: 'invoice_number' },
                { data: 'po_number_label' },
                { data: 'items_count_label' },
                { data: 'invoice_date_formatted' },
                { data: 'grand_total_formatted' },
                { data: 'balance_due_formatted' },
                { data: 'match_status_badge' },
                { data: 'invoice_status_badge' },
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
                        <p class="text-muted mb-0">No purchase invoices available</p>
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
// Purchase Invoice Item Row Builder
// =====================================================
function buildPurchaseInvoiceItemRow(container, itemData) {
    var form = container.closest('form');
    var index = purchaseInvoiceItemRowIndex++;
    var productOptionsHtml = form.find('.purchase-invoice-product-options').html();

    var row = $(`
        <div class="fm-grid purchase-invoice-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select pinv-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control pinv-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control pinv-item-price" name="items[${index}][unit_price]" placeholder="Unit Price" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control pinv-item-discount" name="items[${index}][discount]" placeholder="Discount" value="0">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-purchase-invoice-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.pinv-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.pinv-item-product').val(itemData.product_id);
        row.find('.pinv-item-quantity').val(itemData.quantity);
        row.find('.pinv-item-price').val(itemData.unit_price);
        row.find('.pinv-item-discount').val(itemData.discount);
    }

    container.append(row);
    recalculatePurchaseInvoiceTotals(container);
}

$(document).on('click', '.purchase-invoice-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.purchase-invoice-item-rows');
    buildPurchaseInvoiceItemRow(container);
});

$(document).on('click', '.remove-purchase-invoice-item', function () {
    var container = $(this).closest('.purchase-invoice-item-rows');
    $(this).closest('.purchase-invoice-item-row').remove();
    recalculatePurchaseInvoiceTotals(container);
});

// Auto-fill unit price from the selected product's selling price
$(document).on('change', '.pinv-item-product', function () {
    var selected = $(this).find('option:selected');
    var price = selected.data('price');
    var row = $(this).closest('.purchase-invoice-item-row');
    if (price !== undefined && price !== '' && !row.find('.pinv-item-price').val()) {
        row.find('.pinv-item-price').val(price);
    }
    recalculatePurchaseInvoiceTotals($(this).closest('.purchase-invoice-item-rows'));
});

$(document).on('input', '.pinv-item-quantity, .pinv-item-price, .pinv-item-discount', function () {
    recalculatePurchaseInvoiceTotals($(this).closest('.purchase-invoice-item-rows'));
});

function recalculatePurchaseInvoiceTotals(container) {
    var form = container.closest('form');
    var subtotal = 0;
    var discountTotal = 0;

    container.find('.purchase-invoice-item-row').each(function () {
        var qty = parseFloat($(this).find('.pinv-item-quantity').val()) || 0;
        var price = parseFloat($(this).find('.pinv-item-price').val()) || 0;
        var discount = parseFloat($(this).find('.pinv-item-discount').val()) || 0;

        subtotal += qty * price;
        discountTotal += discount;
    });

    var grandTotal = subtotal - discountTotal;

    form.find('.pinv-subtotal-preview').text(subtotal.toFixed(2));
    form.find('.pinv-discount-preview').text(discountTotal.toFixed(2));
    form.find('.pinv-grandtotal-preview').text(grandTotal.toFixed(2));
}

function populateExistingPurchaseInvoiceItems(scope) {
    $(scope).find('.purchase-invoice-item-rows[data-existing]').each(function () {
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
            buildPurchaseInvoiceItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingPurchaseInvoiceItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTablePurchaseInvoices.init();

    // Search
    $('#purchaseInvoiceSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Vendor filter
    $('#vendorFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Match status filter
    $('#matchStatusFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Invoice status filter
    $('#invoiceStatusFilter').on('change', function () {
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
