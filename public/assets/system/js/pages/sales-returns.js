var dataTableInstance;
var salesReturnItemRowIndex = 0;

var DataTableSalesReturns = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#salesReturnTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#salesReturnTable').data('url'),
                data: function (d) {
                    d.search = $('#salesReturnSearch').val();
                    d.customer_id = $('#customerFilter').val();
                    d.return_status = $('#returnStatusFilter').val();
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
                { data: 'return_number' },
                { data: 'items_count_label' },
                { data: 'return_date_formatted' },
                { data: 'return_status_badge' },
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
                        <p class="text-muted mb-0">No sales returns available</p>
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
// Sales Return Item Row Builder
// =====================================================
function buildSalesReturnItemRow(container, itemData) {
    var form = container.closest('form');
    var index = salesReturnItemRowIndex++;
    var productOptionsHtml = form.find('.sales-return-product-options').html();

    var row = $(`
        <div class="fm-grid sales-return-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select sr-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control sr-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:140px;">
                <select class="form-select sr-item-condition" name="items[${index}][condition]">
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="defective">Defective</option>
                </select>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control sr-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-sales-return-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.sr-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.sr-item-product').val(itemData.product_id);
        row.find('.sr-item-quantity').val(itemData.quantity);
        row.find('.sr-item-condition').val(itemData.condition);
        row.find('.sr-item-notes').val(itemData.notes);
    }

    container.append(row);
}

$(document).on('click', '.sales-return-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.sales-return-item-rows');
    buildSalesReturnItemRow(container);
});

$(document).on('click', '.remove-sales-return-item', function () {
    $(this).closest('.sales-return-item-row').remove();
});

function populateExistingSalesReturnItems(scope) {
    $(scope).find('.sales-return-item-rows[data-existing]').each(function () {
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
            buildSalesReturnItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingSalesReturnItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableSalesReturns.init();

    // Search
    $('#salesReturnSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Customer filter
    $('#customerFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Return status filter
    $('#returnStatusFilter').on('change', function () {
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
