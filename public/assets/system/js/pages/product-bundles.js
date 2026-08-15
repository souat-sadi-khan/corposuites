var dataTableInstance;
var bundleItemRowIndex = 0;

var DataTableProductBundles = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#productBundleTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#productBundleTable').data('url'),
                data: function (d) {
                    d.search = $('#productBundleSearch').val();
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
                { data: 'price_formatted', defaultContent: '-' },
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
                        <p class="text-muted mb-0">No product bundles available</p>
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
// Bundle Item Row Builder
// =====================================================
function buildBundleItemRow(container, itemData) {
    var form = container.closest('form');
    var index = bundleItemRowIndex++;
    var productOptionsHtml = form.find('.bundle-product-options').html();

    var row = $(`
        <div class="fm-grid bundle-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select bundle-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:120px;">
                <input type="number" min="1" class="form-control bundle-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-bundle-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.bundle-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.bundle-item-product').val(itemData.product_id);
        row.find('.bundle-item-quantity').val(itemData.quantity);
    }

    container.append(row);
}

$(document).on('click', '.bundle-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.bundle-item-rows');
    buildBundleItemRow(container);
});

$(document).on('click', '.remove-bundle-item', function () {
    $(this).closest('.bundle-item-row').remove();
});

function populateExistingBundleItems(scope) {
    $(scope).find('.bundle-item-rows[data-existing]').each(function () {
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
            buildBundleItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingBundleItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProductBundles.init();

    // Search
    $('#productBundleSearch').on('keyup', function () {
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
