var dataTableInstance;
var deliveryItemRowIndex = 0;

var DataTableDeliveries = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#deliveryTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#deliveryTable').data('url'),
                data: function (d) {
                    d.search = $('#deliverySearch').val();
                    d.sales_order_id = $('#salesOrderFilter').val();
                    d.delivery_status = $('#deliveryStatusFilter').val();
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
                { data: 'delivery_number' },
                { data: 'items_count_label' },
                { data: 'delivery_date_formatted' },
                { data: 'delivery_status_badge' },
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
                        <p class="text-muted mb-0">No deliveries available</p>
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
// Delivery Item Row Builder
// =====================================================
function buildDeliveryItemRow(container, itemData) {
    var form = container.closest('form');
    var index = deliveryItemRowIndex++;
    var productOptionsHtml = form.find('.delivery-product-options').html();

    var row = $(`
        <div class="fm-grid delivery-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select delivery-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:120px;">
                <input type="number" step="0.01" min="0.01" class="form-control delivery-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-delivery-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.delivery-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.delivery-item-product').val(itemData.product_id);
        row.find('.delivery-item-quantity').val(itemData.quantity);
    }

    container.append(row);
}

$(document).on('click', '.delivery-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.delivery-item-rows');
    buildDeliveryItemRow(container);
});

$(document).on('click', '.remove-delivery-item', function () {
    $(this).closest('.delivery-item-row').remove();
});

function populateExistingDeliveryItems(scope) {
    $(scope).find('.delivery-item-rows[data-existing]').each(function () {
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
            buildDeliveryItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingDeliveryItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableDeliveries.init();

    // Search
    $('#deliverySearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Sales order filter
    $('#salesOrderFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Delivery status filter
    $('#deliveryStatusFilter').on('change', function () {
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
