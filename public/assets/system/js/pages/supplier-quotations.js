var dataTableInstance;
var supplierQuotationItemRowIndex = 0;

var DataTableSupplierQuotations = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#supplierQuotationTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#supplierQuotationTable').data('url'),
                data: function (d) {
                    d.search = $('#supplierQuotationSearch').val();
                    d.vendor_id = $('#vendorFilter').val();
                    d.rfq_id = $('#rfqFilter').val();
                    d.quotation_status = $('#quotationStatusFilter').val();
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
                { data: 'quotation_number' },
                { data: 'rfq_number_label' },
                { data: 'items_count_label' },
                { data: 'quotation_date_formatted' },
                { data: 'grand_total_formatted' },
                { data: 'quotation_status_badge' },
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
                        <p class="text-muted mb-0">No supplier quotations available</p>
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
// Supplier Quotation Item Row Builder
// =====================================================
function buildSupplierQuotationItemRow(container, itemData) {
    var form = container.closest('form');
    var index = supplierQuotationItemRowIndex++;
    var productOptionsHtml = form.find('.supplier-quotation-product-options').html();

    var row = $(`
        <div class="fm-grid supplier-quotation-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select siq-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control siq-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control siq-item-price" name="items[${index}][unit_price]" placeholder="Unit Price" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control siq-item-discount" name="items[${index}][discount]" placeholder="Discount" value="0">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-supplier-quotation-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.siq-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.siq-item-product').val(itemData.product_id);
        row.find('.siq-item-quantity').val(itemData.quantity);
        row.find('.siq-item-price').val(itemData.unit_price);
        row.find('.siq-item-discount').val(itemData.discount);
    }

    container.append(row);
    recalculateSupplierQuotationTotals(container);
}

$(document).on('click', '.supplier-quotation-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.supplier-quotation-item-rows');
    buildSupplierQuotationItemRow(container);
});

$(document).on('click', '.remove-supplier-quotation-item', function () {
    var container = $(this).closest('.supplier-quotation-item-rows');
    $(this).closest('.supplier-quotation-item-row').remove();
    recalculateSupplierQuotationTotals(container);
});

// Auto-fill unit price from the selected product's selling price
$(document).on('change', '.siq-item-product', function () {
    var selected = $(this).find('option:selected');
    var price = selected.data('price');
    var row = $(this).closest('.supplier-quotation-item-row');
    if (price !== undefined && price !== '' && !row.find('.siq-item-price').val()) {
        row.find('.siq-item-price').val(price);
    }
    recalculateSupplierQuotationTotals($(this).closest('.supplier-quotation-item-rows'));
});

$(document).on('input', '.siq-item-quantity, .siq-item-price, .siq-item-discount', function () {
    recalculateSupplierQuotationTotals($(this).closest('.supplier-quotation-item-rows'));
});

function recalculateSupplierQuotationTotals(container) {
    var form = container.closest('form');
    var subtotal = 0;
    var discountTotal = 0;

    container.find('.supplier-quotation-item-row').each(function () {
        var qty = parseFloat($(this).find('.siq-item-quantity').val()) || 0;
        var price = parseFloat($(this).find('.siq-item-price').val()) || 0;
        var discount = parseFloat($(this).find('.siq-item-discount').val()) || 0;

        subtotal += qty * price;
        discountTotal += discount;
    });

    var grandTotal = subtotal - discountTotal;

    form.find('.sq-subtotal-preview').text(subtotal.toFixed(2));
    form.find('.sq-discount-preview').text(discountTotal.toFixed(2));
    form.find('.sq-grandtotal-preview').text(grandTotal.toFixed(2));
}

function populateExistingSupplierQuotationItems(scope) {
    $(scope).find('.supplier-quotation-item-rows[data-existing]').each(function () {
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
            buildSupplierQuotationItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingSupplierQuotationItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableSupplierQuotations.init();

    // Search
    $('#supplierQuotationSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Vendor filter
    $('#vendorFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // RFQ filter
    $('#rfqFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Quotation status filter
    $('#quotationStatusFilter').on('change', function () {
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
