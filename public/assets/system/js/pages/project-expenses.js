var dataTableInstance;

var DataTableProjectExpenses = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#expenseTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#expenseTable').data('url'),
                data: function (d) {
                    d.search = $('#expenseSearch').val();
                    d.project_id = $('#projectFilter').val();
                    d.expense_category = $('#categoryFilter').val();
                    d.approval_status = $('#approvalFilter').val();
                    d.billable = $('#billableFilter').val();
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
                { data: 'title_col' },
                { data: 'project_name' },
                { data: 'paid_to' },
                { data: 'expense_date_formatted' },
                { data: 'amount_col' },
                { data: 'receipt_link' },
                { data: 'approval_badge' },
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
                        <p class="text-muted mb-0">No project expenses available</p>
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
// Approve / Reject — plain confirm + POST + redraw, the same
// shape as sales-commissions.js's mark-paid button.
// =====================================================
$(document).on('click', '.expense-approve-btn', function () {
    if (!confirm('Approve this expense? It will be locked from further edits.')) {
        return;
    }

    $.ajax({
        url: $(this).data('url'),
        type: 'POST',
        success: function (res) {
            if (res.status) {
                dataTableInstance.ajax.reload(null, false);
            } else {
                alert(res.message || 'Unable to approve this expense.');
            }
        },
        error: function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to approve this expense.';
            alert(msg);
        }
    });
});

$(document).on('click', '.expense-reject-btn', function () {
    if (!confirm('Reject this expense?')) {
        return;
    }

    $.ajax({
        url: $(this).data('url'),
        type: 'POST',
        success: function (res) {
            if (res.status) {
                dataTableInstance.ajax.reload(null, false);
            } else {
                alert(res.message || 'Unable to reject this expense.');
            }
        },
        error: function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to reject this expense.';
            alert(msg);
        }
    });
});

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectExpenses.init();

    // Search
    $('#expenseSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#projectFilter, #categoryFilter, #approvalFilter, #billableFilter').on('change', function () {
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
