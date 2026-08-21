var dataTableInstance;
var purchaseRequestItemRowIndex = 0;

var DataTablePurchaseRequests = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#purchaseRequestTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#purchaseRequestTable').data('url'),
                data: function (d) {
                    d.search = $('#purchaseRequestSearch').val();
                    d.department_id = $('#departmentFilter').val();
                    d.request_status = $('#requestStatusFilter').val();
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
                { data: 'request_number' },
                { data: 'department_name' },
                { data: 'items_count_label' },
                { data: 'required_date_formatted' },
                { data: 'request_status_badge' },
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
                        <p class="text-muted mb-0">No purchase requests available</p>
                    </div>
                `
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateTlInfo();
                _componentSwitch();
                bindApprovalButtons();
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
// Approve / Reject
// =====================================================
function bindApprovalButtons() {
    $('button#approvePurchaseRequest, button#rejectPurchaseRequest').off('click').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!confirm('Are you sure you want to perform this action?')) return;
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (response.status) {
                    dataTableInstance.draw();
                } else {
                    alert('Operation failed: ' + (response.message || 'Unknown error'));
                }
            },
            error: function (xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
            }
        });
    });
}

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
// Purchase Request Item Row Builder
// =====================================================
function buildPurchaseRequestItemRow(container, itemData) {
    var form = container.closest('form');
    var index = purchaseRequestItemRowIndex++;
    var productOptionsHtml = form.find('.purchase-request-product-options').html();

    var row = $(`
        <div class="fm-grid purchase-request-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select pr-item-product" name="items[${index}][product_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0.01" class="form-control pr-item-quantity" name="items[${index}][quantity]" placeholder="Qty" value="1" required>
            </div>
            <div class="fm-field">
                <input type="text" class="form-control pr-item-notes" name="items[${index}][notes]" placeholder="Notes">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-purchase-request-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.pr-item-product').html(productOptionsHtml);

    if (itemData) {
        row.find('.pr-item-product').val(itemData.product_id);
        row.find('.pr-item-quantity').val(itemData.quantity);
        row.find('.pr-item-notes').val(itemData.notes);
    }

    container.append(row);
}

$(document).on('click', '.purchase-request-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.purchase-request-item-rows');
    buildPurchaseRequestItemRow(container);
});

$(document).on('click', '.remove-purchase-request-item', function () {
    $(this).closest('.purchase-request-item-row').remove();
});

function populateExistingPurchaseRequestItems(scope) {
    $(scope).find('.purchase-request-item-rows[data-existing]').each(function () {
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
            buildPurchaseRequestItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingPurchaseRequestItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTablePurchaseRequests.init();

    // Search
    $('#purchaseRequestSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Department filter
    $('#departmentFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Request status filter
    $('#requestStatusFilter').on('change', function () {
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
