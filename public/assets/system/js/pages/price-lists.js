var dataTableInstance;
var priceListItemRowIndex = 0;

var DataTablePriceLists = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#priceListTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#priceListTable').data('url'),
                data: function (d) {
                    d.search = $('#priceListSearch').val();
                    d.customer_group_id = $('#customerGroupFilter').val();
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
                { data: 'name' },
                { data: 'items_count_label' },
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
                        <p class="text-muted mb-0">No price lists available</p>
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
// Price List Item Row Builder
// =====================================================
function buildPriceListItemRow(container, itemData) {
    var form = container.closest('form');
    var index = priceListItemRowIndex++;
    var productOptionsHtml = form.find('.price-list-product-options').html();

    var row = $(`
        <div class="fm-grid price-list-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select price-list-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:150px;">
                <input type="number" step="0.01" min="0" class="form-control price-list-item-price" name="items[${index}][price]" placeholder="Price" required>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-price-list-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.price-list-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.price-list-item-product').val(itemData.product_id);
        row.find('.price-list-item-price').val(itemData.price);
    }

    container.append(row);
}

$(document).on('click', '.price-list-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.price-list-item-rows');
    buildPriceListItemRow(container);
});

$(document).on('click', '.remove-price-list-item', function () {
    $(this).closest('.price-list-item-row').remove();
});

function populateExistingPriceListItems(scope) {
    $(scope).find('.price-list-item-rows[data-existing]').each(function () {
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
            buildPriceListItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingPriceListItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTablePriceLists.init();

    // Search
    $('#priceListSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Customer group filter
    $('#customerGroupFilter').on('change', function () {
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
