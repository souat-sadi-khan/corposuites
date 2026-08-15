var dataTableInstance;
var debitNoteItemRowIndex = 0;

var DataTableDebitNotes = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#debitNoteTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#debitNoteTable').data('url'),
                data: function (d) {
                    d.search = $('#debitNoteSearch').val();
                    d.vendor_id = $('#vendorFilter').val();
                    d.debit_status = $('#debitStatusFilter').val();
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
                { data: 'debit_note_number' },
                { data: 'invoice_number_label' },
                { data: 'items_count_label' },
                { data: 'debit_date_formatted' },
                { data: 'grand_total_formatted' },
                { data: 'debit_status_badge' },
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
                        <p class="text-muted mb-0">No debit notes available</p>
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
// Debit Note Item Row Builder
// =====================================================
function buildDebitNoteItemRow(container, itemData) {
    var form = container.closest('form');
    var index = debitNoteItemRowIndex++;
    var productOptionsHtml = form.find('.debit-note-product-options').html();

    var row = $(`
        <div class="fm-grid debit-note-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select dbn-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control dbn-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control dbn-item-price" name="items[${index}][unit_price]" placeholder="Unit Price" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control dbn-item-discount" name="items[${index}][discount]" placeholder="Discount" value="0">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-debit-note-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.dbn-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.dbn-item-product').val(itemData.product_id);
        row.find('.dbn-item-quantity').val(itemData.quantity);
        row.find('.dbn-item-price').val(itemData.unit_price);
        row.find('.dbn-item-discount').val(itemData.discount);
    }

    container.append(row);
    recalculateDebitNoteTotals(container);
}

$(document).on('click', '.debit-note-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.debit-note-item-rows');
    buildDebitNoteItemRow(container);
});

$(document).on('click', '.remove-debit-note-item', function () {
    var container = $(this).closest('.debit-note-item-rows');
    $(this).closest('.debit-note-item-row').remove();
    recalculateDebitNoteTotals(container);
});

// Auto-fill unit price from the selected product's selling price
$(document).on('change', '.dbn-item-product', function () {
    var selected = $(this).find('option:selected');
    var price = selected.data('price');
    var row = $(this).closest('.debit-note-item-row');
    if (price !== undefined && price !== '' && !row.find('.dbn-item-price').val()) {
        row.find('.dbn-item-price').val(price);
    }
    recalculateDebitNoteTotals($(this).closest('.debit-note-item-rows'));
});

$(document).on('input', '.dbn-item-quantity, .dbn-item-price, .dbn-item-discount', function () {
    recalculateDebitNoteTotals($(this).closest('.debit-note-item-rows'));
});

function recalculateDebitNoteTotals(container) {
    var form = container.closest('form');
    var subtotal = 0;
    var discountTotal = 0;

    container.find('.debit-note-item-row').each(function () {
        var qty = parseFloat($(this).find('.dbn-item-quantity').val()) || 0;
        var price = parseFloat($(this).find('.dbn-item-price').val()) || 0;
        var discount = parseFloat($(this).find('.dbn-item-discount').val()) || 0;

        subtotal += qty * price;
        discountTotal += discount;
    });

    var grandTotal = subtotal - discountTotal;

    form.find('.dbn-subtotal-preview').text(subtotal.toFixed(2));
    form.find('.dbn-discount-preview').text(discountTotal.toFixed(2));
    form.find('.dbn-grandtotal-preview').text(grandTotal.toFixed(2));
}

function populateExistingDebitNoteItems(scope) {
    $(scope).find('.debit-note-item-rows[data-existing]').each(function () {
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
            buildDebitNoteItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingDebitNoteItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableDebitNotes.init();

    // Search
    $('#debitNoteSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Vendor filter
    $('#vendorFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Debit status filter
    $('#debitStatusFilter').on('change', function () {
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
