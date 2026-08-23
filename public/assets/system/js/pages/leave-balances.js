var dataTableInstance;
var leaveBalanceItemRowIndex = 0;

var DataTableLeaveBalances = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#leaveBalanceTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            ordering: false, // grouped/rolled-up rows aren't meaningfully single-column sortable
            ajax: {
                url: $('#leaveBalanceTable').data('url'),
                data: function (d) {
                    d.search = $('#leaveBalanceSearch').val();
                    var employeeId = $('#leaveBalanceTable').data('employee-id');
                    if (employeeId) {
                        d.employee_id = employeeId;
                    }
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'employee_name' },
                { data: 'year_label' },
                { data: 'types_summary' },
                { data: 'balance' },
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
                        <p class="text-muted mb-0">No leave balance records available</p>
                    </div>
                `
            },
            drawCallback: function () {
                updateTlInfo();
                if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
                    _componentRemoteModalLoadAfterAjax();
                }
            }
        });
    };

    return {
        init: function () {
            initDataTable();
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
// Manage form — leave-type line row builder
// =====================================================
function buildLeaveBalanceItemRow(container, itemData) {
    var form = container.closest('form');
    var index = leaveBalanceItemRowIndex++;
    var typeOptionsHtml = form.find('.leave-balance-type-options').html();
    var hasId = itemData && itemData.id;
    var canEncash = hasId && itemData.is_encashable;

    var row = $(`
        <div class="fm-grid leave-balance-item-row mb-2" data-item-index="${index}">
            <div class="fm-field" style="max-width:190px;">
                <select class="form-select leave-balance-item-type" name="items[${index}][leave_type_id]" required></select>
            </div>
            <div class="fm-field" style="max-width:110px;">
                <input type="number" step="0.01" min="0" class="form-control leave-balance-item-allocated" name="items[${index}][allocated_days]" placeholder="Allocated" required>
            </div>
            <div class="fm-field" style="max-width:110px;">
                <input type="number" step="0.01" min="0" class="form-control leave-balance-item-used" name="items[${index}][used_days]" placeholder="Used" required>
            </div>
            <div class="fm-field" style="max-width:100px;">
                <input type="number" step="0.01" min="0" class="form-control leave-balance-item-carried" name="items[${index}][carried_days]" placeholder="Carried">
            </div>
            <div class="fm-field leave-balance-item-remaining-wrap" style="max-width:90px;">
                <span class="small text-muted">Remaining</span>
                <div class="fw-bold leave-balance-item-remaining">0.00</div>
            </div>
            <div class="fm-field" style="max-width:50px;">
                <div class="form-check form-switch mt-2">
                    <!-- Hidden "0" fires first, so an UNCHECKED box still
                         submits status=0 — a plain checkbox alone is simply
                         omitted from the request when unchecked, which
                         would otherwise silently default this line back to
                         active on save (LeaveBalanceGroupRequest's own
                         status rule is nullable, and the service falls back
                         to true when the key is missing entirely). -->
                    <input type="hidden" name="items[${index}][status]" value="0">
                    <input type="checkbox" class="form-check-input leave-balance-item-status" name="items[${index}][status]" value="1" checked title="Active">
                </div>
            </div>
            <div class="fm-field leave-balance-item-encash-wrap" style="max-width:60px;${canEncash ? '' : 'display:none;'}">
                <button type="button" class="btn-nx-outline btn-sm success encash-balance"
                        data-url="${canEncash ? window.leaveBalanceEncashUrlTemplate.replace('__ID__', itemData.id) : ''}"
                        data-remaining="${canEncash ? itemData.remaining_days : 0}"
                        title="Encash remaining balance">
                    <i class="ri-money-dollar-circle-line"></i>
                </button>
            </div>
            <div class="fm-field" style="max-width:60px;">
                <button type="button" class="btn-nx-outline btn-sm remove-leave-balance-item">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    row.find('.leave-balance-item-type').html(typeOptionsHtml);

    if (itemData) {
        row.find('.leave-balance-item-type').val(itemData.leave_type_id);
        row.find('.leave-balance-item-allocated').val(itemData.allocated_days);
        row.find('.leave-balance-item-used').val(itemData.used_days);
        row.find('.leave-balance-item-carried').val(itemData.carried_days || 0);
        row.find('.leave-balance-item-status').prop('checked', !!itemData.status);
        if (itemData.remaining_days !== undefined) {
            row.find('.leave-balance-item-remaining').text(parseFloat(itemData.remaining_days).toFixed(2));
        }
    }

    container.append(row);
    recalculateLeaveBalanceRemaining(row);
}

function recalculateLeaveBalanceRemaining(row) {
    var allocated = parseFloat(row.find('.leave-balance-item-allocated').val()) || 0;
    var used = parseFloat(row.find('.leave-balance-item-used').val()) || 0;
    row.find('.leave-balance-item-remaining').text((allocated - used).toFixed(2));
}

$(document).on('click', '.leave-balance-item-add', function () {
    var form = $(this).closest('form');
    buildLeaveBalanceItemRow(form.find('.leave-balance-item-rows'));
});

$(document).on('click', '.remove-leave-balance-item', function () {
    $(this).closest('.leave-balance-item-row').remove();
});

$(document).on('input', '.leave-balance-item-allocated, .leave-balance-item-used', function () {
    recalculateLeaveBalanceRemaining($(this).closest('.leave-balance-item-row'));
});

function populateExistingLeaveBalanceItems(scope) {
    $(scope).find('.leave-balance-item-rows[data-existing]').each(function () {
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
            buildLeaveBalanceItemRow(container, item);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingLeaveBalanceItems(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableLeaveBalances.init();

    // Search
    $('#leaveBalanceSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous / Next
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    // Generate balances (auto-allocate from policy)
    $('#generateBalances').on('click', function () {
        var url = $(this).data('url');
        var employeeId = $(this).data('employee-id');
        var msg = employeeId
            ? 'Generate leave balances for the selected employee for the current year?'
            : 'Generate leave balances for ALL active employees for the current year?';
        if (!confirm(msg)) return;

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                employee_id: employeeId || null
            },
            success: function (res) {
                notifyBalance(res.status ? 'success' : 'error', res.message || 'Done.');
                if (res.status) dataTableInstance.draw();
            },
            error: function (xhr) {
                notifyBalance('error', xhr.responseJSON?.message || 'Something went wrong');
            }
        });
    });

    // Encash remaining balance (per leave-type row, still a real
    // LeaveBalance id — same endpoint as before this restructuring)
    $(document).on('click', '.encash-balance', function () {
        var url = $(this).data('url');
        var remaining = $(this).data('remaining');
        var input = prompt('Days to encash (max ' + remaining + '). Leave blank to encash all remaining:', '');
        if (input === null) return; // cancelled

        var data = { _token: $('meta[name="csrf-token"]').attr('content') };
        if (input.trim() !== '') data.days = input.trim();

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function (res) {
                notifyBalance(res.status ? 'success' : 'error', res.message || 'Done.');
                if (res.status) { $('#modal_remote').modal('hide'); dataTableInstance.draw(); }
            },
            error: function (xhr) {
                notifyBalance('error', xhr.responseJSON?.message || 'Something went wrong');
            }
        });
    });
});

function notifyBalance(type, msg) {
    if (typeof Lobibox !== 'undefined') {
        Lobibox.notify(type, {
            size: 'mini', rounded: true, position: 'bottom right',
            icon: type === 'success' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line',
            msg: msg
        });
    } else {
        alert(msg);
    }
}
