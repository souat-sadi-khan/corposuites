var dataTableInstance;
var bankReconciliationItemRowIndex = 0;

var DataTableBankReconciliations = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#bankReconciliationTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#bankReconciliationTable').data('url'),
                data: function (d) {
                    d.search = $('#bankReconciliationSearch').val();
                    d.finance_bank_account_id = $('#bankAccountFilter').val();
                    d.reconciliation_status = $('#reconciliationStatusFilter').val();
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
                { data: 'reconciliation_number' },
                { data: 'items_count_label' },
                { data: 'statement_date_formatted' },
                { data: 'variance_formatted' },
                { data: 'reconciliation_status_badge' },
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
                        <p class="text-muted mb-0">No bank reconciliations available</p>
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
// Bank Reconciliation Item Row Builder
// =====================================================
function buildBankReconciliationItemRow(container, itemData) {
    var form = container.closest('form');
    var index = bankReconciliationItemRowIndex++;
    var transactionOptionsHtml = form.find('.bank-reconciliation-transaction-options').html();

    var row = $(`
        <div class="fm-grid bank-reconciliation-item-row mb-2" data-item-index="${index}">
            <div class="fm-field fm-full">
                <select class="form-select select brc-item-transaction" name="items[${index}][bank_transaction_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-bank-reconciliation-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.brc-item-transaction').html(transactionOptionsHtml);

    if (itemData) {
        row.find('.brc-item-transaction').val(itemData.bank_transaction_id);
    }

    container.append(row);
    recalculateBankReconciliationPreview(container);
}

$(document).on('click', '.bank-reconciliation-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.bank-reconciliation-item-rows');
    buildBankReconciliationItemRow(container);
});

$(document).on('click', '.remove-bank-reconciliation-item', function () {
    var container = $(this).closest('.bank-reconciliation-item-rows');
    $(this).closest('.bank-reconciliation-item-row').remove();
    recalculateBankReconciliationPreview(container);
});

$(document).on('change', '.brc-item-transaction', function () {
    recalculateBankReconciliationPreview($(this).closest('.bank-reconciliation-item-rows'));
});

$(document).on('input', '.brc-opening, .brc-closing', function () {
    var container = $(this).closest('form').find('.bank-reconciliation-item-rows');
    recalculateBankReconciliationPreview(container);
});

function recalculateBankReconciliationPreview(container) {
    var form = container.closest('form');
    var opening = parseFloat(form.find('.brc-opening').val()) || 0;
    var closing = parseFloat(form.find('.brc-closing').val()) || 0;
    var running = opening;

    container.find('.brc-item-transaction').each(function () {
        var selected = $(this).find('option:selected');
        var amount = parseFloat(selected.data('amount')) || 0;
        var type = selected.data('type');

        running += (type === 'withdrawal') ? -amount : amount;
    });

    var computed = Math.round(running * 100) / 100;
    var variance = Math.round((closing - computed) * 100) / 100;

    form.find('.brc-computed-preview').text(computed.toFixed(2));
    form.find('.brc-variance-preview').text(variance.toFixed(2)).css('color', variance === 0 ? '' : '#dc2626');
}

function populateExistingBankReconciliationItems(scope) {
    $(scope).find('.bank-reconciliation-item-rows[data-existing]').each(function () {
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
            buildBankReconciliationItemRow(container, item);
        });

        recalculateBankReconciliationPreview(container);
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingBankReconciliationItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableBankReconciliations.init();

    // Search
    $('#bankReconciliationSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Bank account filter
    $('#bankAccountFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Reconciliation status filter
    $('#reconciliationStatusFilter').on('change', function () {
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
