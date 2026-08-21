var dataTableInstance;
var projectInvoiceItemRowIndex = 0;

var DataTableProjectInvoices = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#invoiceTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#invoiceTable').data('url'),
                data: function (d) {
                    d.search = $('#invoiceSearch').val();
                    d.project_id = $('#projectFilter').val();
                    d.invoice_status = $('#invoiceStatusFilter').val();
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
                { data: 'invoice_number_col' },
                { data: 'items_count_label' },
                { data: 'invoice_date_formatted' },
                { data: 'grand_total_formatted' },
                { data: 'balance_due_formatted' },
                { data: 'invoice_status_badge' },
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
                        <p class="text-muted mb-0">No invoices available</p>
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
// Invoice Line Row Builder — one "source" select combines the manual
// option with every pickable time entry and expense. Choosing a real
// source auto-fills description/quantity/unit price (still editable)
// and stamps the two hidden id fields so only the relevant one carries
// a value; choosing Manual clears both.
// =====================================================
function buildProjectInvoiceItemRow(container, itemData) {
    var form = container.closest('form');
    var index = projectInvoiceItemRowIndex++;
    var timeEntryOptionsHtml = form.find('.pinv-time-entry-options').html();
    var expenseOptionsHtml = form.find('.pinv-expense-options').html();

    var row = $(`
        <div class="fm-grid pinv-item-row mb-2" data-item-index="${index}">
            <div class="fm-field">
                <label class="small text-muted mb-1">Source</label>
                <select class="form-select select pinv-item-source">
                    <option value="manual">— Manual line —</option>
                    <optgroup label="Time Entries" class="pinv-item-source-time"></optgroup>
                    <optgroup label="Expenses" class="pinv-item-source-expense"></optgroup>
                </select>
            </div>
            <div class="fm-field">
                <label class="small text-muted mb-1">Description</label>
                <input type="text" class="form-control pinv-item-description" name="items[${index}][description]" required>
            </div>
            <div class="fm-field" style="max-width:110px;">
                <label class="small text-muted mb-1">Qty / Hours</label>
                <input type="number" step="0.01" min="0.01" class="form-control pinv-item-quantity" name="items[${index}][quantity]" value="1" required>
            </div>
            <div class="fm-field" style="max-width:130px;">
                <label class="small text-muted mb-1">Unit Price</label>
                <input type="number" step="0.01" min="0" class="form-control pinv-item-price" name="items[${index}][unit_price]" value="0" required>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <label class="small text-muted mb-1">&nbsp;</label><br>
                <button type="button" class="btn-nx-outline btn-sm remove-pinv-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
            <input type="hidden" class="pinv-item-source-type" name="items[${index}][source_type]" value="manual">
            <input type="hidden" class="pinv-item-time-entry-id" name="items[${index}][project_time_entry_id]" value="">
            <input type="hidden" class="pinv-item-expense-id" name="items[${index}][project_expense_id]" value="">
        </div>
    `);

    row.find('.pinv-item-source-time').html(timeEntryOptionsHtml.replace('<option value="">Select time entry</option>', ''));
    row.find('.pinv-item-source-expense').html(expenseOptionsHtml.replace('<option value="">Select expense</option>', ''));
    row.find('.pinv-item-source option[value=""]').remove();
    row.find('.pinv-item-source-time option').each(function () { $(this).val('te-' + $(this).val()); });
    row.find('.pinv-item-source-expense option').each(function () { $(this).val('exp-' + $(this).val()); });

    if (itemData) {
        row.find('.pinv-item-description').val(itemData.description);
        row.find('.pinv-item-quantity').val(itemData.quantity);
        row.find('.pinv-item-price').val(itemData.unit_price);
        row.find('.pinv-item-source-type').val(itemData.source_type);
        row.find('.pinv-item-time-entry-id').val(itemData.project_time_entry_id || '');
        row.find('.pinv-item-expense-id').val(itemData.project_expense_id || '');

        if (itemData.source_type === 'time_entry' && itemData.project_time_entry_id) {
            row.find('.pinv-item-source').val('te-' + itemData.project_time_entry_id);
        } else if (itemData.source_type === 'expense' && itemData.project_expense_id) {
            row.find('.pinv-item-source').val('exp-' + itemData.project_expense_id);
        }
    }

    container.append(row);
    filterProjectInvoiceItemSources(form);
    recalculateProjectInvoiceTotals(container);
}

$(document).on('click', '.pinv-item-add', function () {
    var form = $(this).closest('form');
    var container = form.find('.pinv-item-rows');
    buildProjectInvoiceItemRow(container);
});

$(document).on('click', '.remove-pinv-item', function () {
    var container = $(this).closest('.pinv-item-rows');
    $(this).closest('.pinv-item-row').remove();
    recalculateProjectInvoiceTotals(container);
});

// When a real source is picked, auto-fill the line and stamp the hidden
// fields; picking Manual clears both hidden ids so nothing gets billed
// twice by accident.
$(document).on('change', '.pinv-item-source', function () {
    var row = $(this).closest('.pinv-item-row');
    var value = $(this).val();

    if (!value || value === 'manual') {
        row.find('.pinv-item-source-type').val('manual');
        row.find('.pinv-item-time-entry-id').val('');
        row.find('.pinv-item-expense-id').val('');
        recalculateProjectInvoiceTotals(row.closest('.pinv-item-rows'));
        return;
    }

    var option = $(this).find('option:selected');
    var isTimeEntry = value.indexOf('te-') === 0;
    var id = value.replace(/^te-|^exp-/, '');

    row.find('.pinv-item-source-type').val(isTimeEntry ? 'time_entry' : 'expense');
    row.find('.pinv-item-time-entry-id').val(isTimeEntry ? id : '');
    row.find('.pinv-item-expense-id').val(isTimeEntry ? '' : id);

    var description = option.data('description');
    if (description) {
        row.find('.pinv-item-description').val(description);
    }

    var quantity = option.data('quantity');
    if (quantity !== undefined && quantity !== '') {
        row.find('.pinv-item-quantity').val(quantity);
    }

    var unitPrice = option.data('unit-price');
    if (unitPrice !== undefined && unitPrice !== '') {
        row.find('.pinv-item-price').val(unitPrice);
    }

    recalculateProjectInvoiceTotals(row.closest('.pinv-item-rows'));
});

$(document).on('input', '.pinv-item-quantity, .pinv-item-price, .pinv-discount-input, .pinv-tax-input', function () {
    var container = $(this).closest('form').find('.pinv-item-rows');
    recalculateProjectInvoiceTotals(container);
});

// =====================================================
// Every source option carries the project it belongs to — narrow both
// optgroups in every row to the header's selected project, and hide the
// header-level un-narrowed hidden selects too. A row whose current
// selection no longer belongs to the selected project falls back to
// Manual rather than silently keeping a foreign selection.
// =====================================================
function filterProjectInvoiceItemSources(form) {
    var projectId = form.find('.pinv-project-select').val();

    form.find('.pinv-item-row').each(function () {
        var row = $(this);
        var select = row.find('.pinv-item-source');
        var current = select.val();
        var currentStillValid = !projectId || current === 'manual' || !current;

        select.find('option').each(function () {
            var option = $(this);
            if (option.val() === 'manual') {
                return;
            }
            var matches = !projectId || String(option.data('project-id')) === String(projectId);
            option.toggle(matches);
            if (matches && option.val() === current) {
                currentStillValid = true;
            }
        });

        if (!currentStillValid) {
            select.val('manual').trigger('change');
        }
    });
}

$(document).on('change', '.pinv-project-select', function () {
    filterProjectInvoiceItemSources($(this).closest('form'));
});

// =====================================================
// Live totals preview — the authoritative figures are always recomputed
// server-side on save; this is purely a UX convenience.
// =====================================================
function recalculateProjectInvoiceTotals(container) {
    var form = container.closest('form');
    var subtotal = 0;

    container.find('.pinv-item-row').each(function () {
        var quantity = parseFloat($(this).find('.pinv-item-quantity').val()) || 0;
        var price = parseFloat($(this).find('.pinv-item-price').val()) || 0;
        subtotal += quantity * price;
    });

    var discount = parseFloat(form.find('.pinv-discount-input').val()) || 0;
    var tax = parseFloat(form.find('.pinv-tax-input').val()) || 0;
    var grandTotal = Math.max(0, subtotal - discount + tax);

    form.find('.pinv-subtotal-preview').text(subtotal.toFixed(2));
    form.find('.pinv-discount-preview').text(discount.toFixed(2));
    form.find('.pinv-tax-preview').text(tax.toFixed(2));
    form.find('.pinv-grandtotal-preview').text(grandTotal.toFixed(2));
}

function populateExistingProjectInvoiceItems(scope) {
    $(scope).find('.pinv-item-rows[data-existing]').each(function () {
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
            buildProjectInvoiceItemRow(container, item);
        });

        filterProjectInvoiceItemSources(container.closest('form'));
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingProjectInvoiceItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Mark Sent / Cancel — plain confirm + POST + redraw.
// =====================================================
function projectInvoicePost(url, data) {
    $.ajax({
        url: url,
        type: 'POST',
        data: data || {},
        success: function (res) {
            if (res.status) {
                dataTableInstance.ajax.reload(null, false);
            } else {
                alert(res.message || 'Something went wrong.');
            }
        },
        error: function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong.';
            alert(msg);
        }
    });
}

$(document).on('click', '.invoice-mark-sent-btn', function () {
    if (!confirm('Mark this invoice as sent?')) {
        return;
    }
    projectInvoicePost($(this).data('url'));
});

$(document).on('click', '.invoice-cancel-btn', function () {
    if (!confirm('Cancel this invoice? Any billed time entries or expenses become billable again.')) {
        return;
    }
    projectInvoicePost($(this).data('url'));
});

// =====================================================
// Record Payment — the only action needing a number from the admin,
// kept to a plain prompt() rather than a second modal for one field,
// the same deliberate choice project-timesheets.js's reject flow made.
// =====================================================
$(document).on('click', '.invoice-record-payment-btn', function () {
    var amount = prompt('Payment amount to record:');

    if (amount === null) {
        return;
    }

    var parsed = parseFloat(amount);
    if (isNaN(parsed) || parsed <= 0) {
        alert('Enter a valid payment amount greater than zero.');
        return;
    }

    projectInvoicePost($(this).data('url'), { amount: parsed });
});

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectInvoices.init();

    // Search
    $('#invoiceSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#projectFilter, #invoiceStatusFilter').on('change', function () {
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
