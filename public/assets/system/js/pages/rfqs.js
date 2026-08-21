var dataTableInstance;
var rfqItemRowIndex = 0;

var DataTableRfqs = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#rfqTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#rfqTable').data('url'),
                data: function (d) {
                    d.search = $('#rfqSearch').val();
                    d.purchase_request_id = $('#purchaseRequestFilter').val();
                    d.rfq_status = $('#rfqStatusFilter').val();
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
                { data: 'rfq_number' },
                { data: 'items_count_label' },
                { data: 'vendors_count_label' },
                { data: 'rfq_date_formatted' },
                { data: 'due_date_formatted' },
                { data: 'rfq_status_badge' },
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
                        <p class="text-muted mb-0">No RFQs available</p>
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
// RFQ Item Row Builder
// =====================================================
function buildRfqItemRow(container, itemData) {
    var form = container.closest('form');
    var index = rfqItemRowIndex++;
    var productOptionsHtml = form.find('.rfq-product-options').html();

    var row = $(`
        <div class="fm-grid rfq-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select rfq-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control rfq-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control rfq-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-rfq-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.rfq-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.rfq-item-product').val(itemData.product_id);
        row.find('.rfq-item-quantity').val(itemData.quantity);
        row.find('.rfq-item-notes').val(itemData.notes);
    }

    container.append(row);
}

$(document).on('click', '.rfq-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.rfq-item-rows');
    buildRfqItemRow(container);
});

$(document).on('click', '.remove-rfq-item', function () {
    $(this).closest('.rfq-item-row').remove();
});

function populateExistingRfqItems(scope) {
    $(scope).find('.rfq-item-rows[data-existing]').each(function () {
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
            buildRfqItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingRfqItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableRfqs.init();

    // Search
    $('#rfqSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Source purchase request filter
    $('#purchaseRequestFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // RFQ status filter
    $('#rfqStatusFilter').on('change', function () {
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
