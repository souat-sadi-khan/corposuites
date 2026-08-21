var dataTableInstance;
var journalEntryItemRowIndex = 0;

var DataTableJournalEntries = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#journalEntryTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#journalEntryTable').data('url'),
                data: function (d) {
                    d.search = $('#journalEntrySearch').val();
                    d.entry_status = $('#entryStatusFilter').val();
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
                { data: 'entry_number' },
                { data: 'items_count_label' },
                { data: 'entry_date_formatted' },
                { data: 'total_debit_formatted' },
                { data: 'entry_status_badge' },
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
                        <p class="text-muted mb-0">No journal entries available</p>
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
// Journal Entry Line Row Builder
// =====================================================
function buildJournalEntryItemRow(container, itemData) {
    var form = container.closest('form');
    var index = journalEntryItemRowIndex++;
    var accountOptionsHtml = form.find('.journal-entry-account-options').html();

    var row = $(`
        <div class="fm-grid journal-entry-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <select class="form-select select je-item-account" name="items[${index}][chart_of_account_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control je-item-debit" name="items[${index}][debit]" placeholder="Debit" value="0">
            </div>
            <div class="fm-field" style="max-width:130px;">
                <input type="number" step="0.01" min="0" class="form-control je-item-credit" name="items[${index}][credit]" placeholder="Credit" value="0">
            </div>
            <div class="fm-field">
                <input type="text" class="form-control je-item-description" name="items[${index}][description]" placeholder="Line description (optional)">
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-journal-entry-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.je-item-account').html(accountOptionsHtml);

    if (itemData) {
        row.find('.je-item-account').val(itemData.chart_of_account_id);
        row.find('.je-item-debit').val(itemData.debit);
        row.find('.je-item-credit').val(itemData.credit);
        row.find('.je-item-description').val(itemData.description);
    }

    container.append(row);
    recalculateJournalEntryTotals(container);
}

$(document).on('click', '.journal-entry-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.journal-entry-item-rows');
    buildJournalEntryItemRow(container);
});

$(document).on('click', '.remove-journal-entry-item', function () {
    var container = $(this).closest('.journal-entry-item-rows');
    $(this).closest('.journal-entry-item-row').remove();
    recalculateJournalEntryTotals(container);
});

// A line is either a debit or a credit, never both — entering one clears the other.
$(document).on('input', '.je-item-debit', function () {
    var val = parseFloat($(this).val()) || 0;
    var row = $(this).closest('.journal-entry-item-row');
    if (val > 0) {
        row.find('.je-item-credit').val(0);
    }
    recalculateJournalEntryTotals(row.closest('.journal-entry-item-rows'));
});

$(document).on('input', '.je-item-credit', function () {
    var val = parseFloat($(this).val()) || 0;
    var row = $(this).closest('.journal-entry-item-row');
    if (val > 0) {
        row.find('.je-item-debit').val(0);
    }
    recalculateJournalEntryTotals(row.closest('.journal-entry-item-rows'));
});

function recalculateJournalEntryTotals(container) {
    var form = container.closest('form');
    var totalDebit = 0;
    var totalCredit = 0;
    var lineCount = container.find('.journal-entry-item-row').length;

    container.find('.journal-entry-item-row').each(function () {
        totalDebit += parseFloat($(this).find('.je-item-debit').val()) || 0;
        totalCredit += parseFloat($(this).find('.je-item-credit').val()) || 0;
    });

    var balance = totalDebit - totalCredit;

    form.find('.je-debit-preview').text(totalDebit.toFixed(2));
    form.find('.je-credit-preview').text(totalCredit.toFixed(2));
    form.find('.je-balance-preview').text(balance.toFixed(2));

    var badge = form.find('.je-balance-badge');
    if (lineCount < 2) {
        badge.removeClass('bg-success bg-danger').addClass('bg-secondary').text('Add lines');
    } else if (Math.abs(balance) < 0.005) {
        badge.removeClass('bg-secondary bg-danger').addClass('bg-success').text('Balanced');
    } else {
        badge.removeClass('bg-secondary bg-success').addClass('bg-danger').text('Out of balance');
    }
}

function populateExistingJournalEntryItems(scope) {
    $(scope).find('.journal-entry-item-rows[data-existing]').each(function () {
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
            buildJournalEntryItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingJournalEntryItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableJournalEntries.init();

    // Search
    $('#journalEntrySearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Entry status filter
    $('#entryStatusFilter').on('change', function () {
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
