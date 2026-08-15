var dataTableInstance;
var salesInvoiceItemRowIndex = 0;

var DataTableSalesInvoices = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#salesInvoiceTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#salesInvoiceTable').data('url'),
                data: function (d) {
                    d.search = $('#salesInvoiceSearch').val();
                    d.customer_id = $('#customerFilter').val();
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
                { data: 'items_count_label' },
                { data: 'invoice_date_formatted' },
                { data: 'grand_total_formatted' },
                { data: 'balance_due_formatted' },
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
                        <p class="text-muted mb-0">No sales invoices available</p>
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
// Sales Invoice Item Row Builder
// =====================================================
function buildSalesInvoiceItemRow(container, itemData) {
    var form = container.closest('form');
    var index = salesInvoiceItemRowIndex++;
    var productOptionsHtml = form.find('.sales-invoice-product-options').html();

    var row = $(`
        <div class="fm-grid sales-invoice-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select si-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control si-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control si-item-price" name="items[${index}][unit_price]" placeholder="Unit Price" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control si-item-discount" name="items[${index}][discount]" placeholder="Discount" value="0">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-sales-invoice-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.si-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.si-item-product').val(itemData.product_id);
        row.find('.si-item-quantity').val(itemData.quantity);
        row.find('.si-item-price').val(itemData.unit_price);
        row.find('.si-item-discount').val(itemData.discount);
    }

    container.append(row);
    recalculateSalesInvoiceTotals(container);
}

$(document).on('click', '.sales-invoice-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.sales-invoice-item-rows');
    buildSalesInvoiceItemRow(container);
});

$(document).on('click', '.remove-sales-invoice-item', function () {
    var container = $(this).closest('.sales-invoice-item-rows');
    $(this).closest('.sales-invoice-item-row').remove();
    recalculateSalesInvoiceTotals(container);
});

// Auto-fill unit price from the selected product's selling price
$(document).on('change', '.si-item-product', function () {
    var selected = $(this).find('option:selected');
    var price = selected.data('price');
    var row = $(this).closest('.sales-invoice-item-row');
    if (price !== undefined && price !== '' && !row.find('.si-item-price').val()) {
        row.find('.si-item-price').val(price);
    }
    recalculateSalesInvoiceTotals($(this).closest('.sales-invoice-item-rows'));
});

$(document).on('input', '.si-item-quantity, .si-item-price, .si-item-discount', function () {
    recalculateSalesInvoiceTotals($(this).closest('.sales-invoice-item-rows'));
});

function recalculateSalesInvoiceTotals(container) {
    var form = container.closest('form');
    var subtotal = 0;
    var discountTotal = 0;

    container.find('.sales-invoice-item-row').each(function () {
        var qty = parseFloat($(this).find('.si-item-quantity').val()) || 0;
        var price = parseFloat($(this).find('.si-item-price').val()) || 0;
        var discount = parseFloat($(this).find('.si-item-discount').val()) || 0;

        subtotal += qty * price;
        discountTotal += discount;
    });

    var grandTotal = subtotal - discountTotal;

    form.find('.si-subtotal-preview').text(subtotal.toFixed(2));
    form.find('.si-discount-preview').text(discountTotal.toFixed(2));
    form.find('.si-grandtotal-preview').text(grandTotal.toFixed(2));
}

function populateExistingSalesInvoiceItems(scope) {
    $(scope).find('.sales-invoice-item-rows[data-existing]').each(function () {
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
            buildSalesInvoiceItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingSalesInvoiceItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableSalesInvoices.init();

    // Search
    $('#salesInvoiceSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Customer filter
    $('#customerFilter').on('change', function () {
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
