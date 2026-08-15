var dataTableInstance;

var DataTableTicketEscalations = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#ticketEscalationTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: false,
            searching: true,
            order: [],
            ajax: {
                url: $('#ticketEscalationTable').data('url'),
                data: function (d) {
                    d.search = $('#ticketEscalationSearch').val();
                    d.ticket_id = $('#ticketFilter').val();
                    d.escalation_rule_id = $('#ruleFilter').val();
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'ticket_col' },
                { data: 'rule_col' },
                { data: 'priority_change_col' },
                { data: 'escalated_to_col' },
                { data: 'escalated_at_formatted' },
                { data: 'status_badge' }
            ],
            language: {
                emptyTable: `
                    <div class="text-center py-4">
                        <img src="${window.location.origin}/assets/images/nothing-to-show.png" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No tickets have been escalated yet</p>
                    </div>
                `
            },
            drawCallback: function () {
                updateTlInfo();
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
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableTicketEscalations.init();

    $('#ticketEscalationSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    $('#ticketFilter, #ruleFilter').on('change', function () {
        dataTableInstance.draw();
    });

    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });
});
