var dataTableInstance;
var paymentMakeItemRowIndex = 0;

var DataTablePaymentMakes = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#paymentMakeTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#paymentMakeTable').data('url'),
                data: function (d) {
                    d.search = $('#paymentMakeSearch').val();
                    d.vendor_id = $('#vendorFilter').val();
                    d.payment_method = $('#paymentMethodFilter').val();
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
                { data: 'payment_number' },
                { data: 'bank_account_label' },
                { data: 'items_count_label' },
                { data: 'payment_date_formatted' },
                { data: 'payment_method_badge' },
                { data: 'amount_formatted' },
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
                        <p class="text-muted mb-0">No payment makes available</p>
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
// Payment Make Item Row Builder
// =====================================================
function buildPaymentMakeItemRow(container, itemData) {
    var form = container.closest('form');
    var index = paymentMakeItemRowIndex++;
    var invoiceOptionsHtml = form.find('.payment-make-invoice-options').html();

    var row = $(`
        <div class="fm-grid payment-make-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select pm-item-invoice" name="items[${index}][purchase_invoice_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:150px;">
                <input type="number" step="0.01" min="0.01" class="form-control pm-item-amount" name="items[${index}][amount_allocated]" placeholder="Amount" required>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-payment-make-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.pm-item-invoice').html(invoiceOptionsHtml);

    if (itemData) {
        row.find('.pm-item-invoice').val(itemData.purchase_invoice_id);
        row.find('.pm-item-amount').val(itemData.amount_allocated);
    }

    container.append(row);
    filterPaymentMakeInvoiceOptions(form);
    recalculatePaymentMakeTotal(container);
}

$(document).on('click', '.payment-make-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.payment-make-item-rows');
    buildPaymentMakeItemRow(container);
});

$(document).on('click', '.remove-payment-make-item', function () {
    var container = $(this).closest('.payment-make-item-rows');
    $(this).closest('.payment-make-item-row').remove();
    recalculatePaymentMakeTotal(container);
});

// Auto-fill the allocation amount from the selected invoice's balance due
$(document).on('change', '.pm-item-invoice', function () {
    var selected = $(this).find('option:selected');
    var balance = selected.data('balance');
    var row = $(this).closest('.payment-make-item-row');
    if (balance !== undefined && balance !== '' && !row.find('.pm-item-amount').val()) {
        row.find('.pm-item-amount').val(balance);
    }
    recalculatePaymentMakeTotal($(this).closest('.payment-make-item-rows'));
});

$(document).on('input', '.pm-item-amount', function () {
    recalculatePaymentMakeTotal($(this).closest('.payment-make-item-rows'));
});

// When the header vendor changes, filter every item row's invoice
// dropdown to only that vendor's invoices, and clear any row selection
// that no longer belongs to the newly selected vendor.
$(document).on('change', '.pm-vendor-select', function () {
    filterPaymentMakeInvoiceOptions($(this).closest('form'));
});

function filterPaymentMakeInvoiceOptions(form) {
    var vendorId = form.find('.pm-vendor-select').val();

    form.find('.pm-item-invoice').each(function () {
        var select = $(this);
        var currentVal = select.val();

        select.find('option').each(function () {
            var option = $(this);
            if (!option.val()) {
                return;
            }
            var matches = !vendorId || String(option.data('vendor-id')) === String(vendorId);
            option.toggle(matches);
        });

        if (currentVal) {
            var selectedOption = select.find('option[value="' + currentVal + '"]');
            if (selectedOption.length && !selectedOption.is(':visible') && vendorId) {
                select.val('');
            }
        }
    });
}

function recalculatePaymentMakeTotal(container) {
    var form = container.closest('form');
    var total = 0;

    container.find('.payment-make-item-row').each(function () {
        var amount = parseFloat($(this).find('.pm-item-amount').val()) || 0;
        total += amount;
    });

    form.find('.pm-amount-preview').text(total.toFixed(2));
}

function populateExistingPaymentMakeItems(scope) {
    $(scope).find('.payment-make-item-rows[data-existing]').each(function () {
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
            buildPaymentMakeItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingPaymentMakeItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTablePaymentMakes.init();

    $('#paymentMakeSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    $('#vendorFilter').on('change', function () {
        dataTableInstance.draw();
    });

    $('#paymentMethodFilter').on('change', function () {
        dataTableInstance.draw();
    });

    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

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
