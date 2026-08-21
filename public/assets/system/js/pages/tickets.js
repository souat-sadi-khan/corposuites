var dataTableInstance;

var DataTableTickets = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#ticketTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#ticketTable').data('url'),
                data: function (d) {
                    d.search = $('#ticketSearch').val();
                    d.ticket_category_id = $('#ticketCategoryFilter').val();
                    d.ticket_status = $('#ticketStatusFilter').val();
                    d.priority = $('#priorityFilter').val();
                    d.overdue = $('#overdueFilter').val();
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
                { data: 'subject_col' },
                { data: 'category_name' },
                { data: 'requester_col' },
                { data: 'due_col' },
                { data: 'priority_badge' },
                { data: 'ticket_status_badge' },
                { data: 'sla_col' },
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
                        <p class="text-muted mb-0">No tickets available</p>
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
// Shared narrowing logic: a "custom" select's options are limited to
// the ones whose data-maps-to matches a "fixed enum" select's current
// value, mirroring the TicketRequest cross-consistency checks
// server-side (ticket_status_id vs ticket_status, ticket_priority_id
// vs priority) so the form rarely trips either one. Clears the
// selection if it no longer applies.
// =====================================================
function filterTicketMappedOptions($scope, fixedSelector, customSelector) {
    var $fixedSelect = $scope.find(fixedSelector);
    var $customSelect = $scope.find(customSelector);

    if (!$fixedSelect.length || !$customSelect.length) {
        return;
    }

    var fixedValue = $fixedSelect.val();
    var selected = $customSelect.val();
    var stillValid = false;

    $customSelect.find('option').each(function () {
        var $option = $(this);
        var mapsTo = $option.data('maps-to');

        if (!mapsTo) {
            return;
        }

        var matches = mapsTo === fixedValue;
        $option.toggle(matches).prop('disabled', !matches);

        if (matches && $option.val() === selected) {
            stillValid = true;
        }
    });

    if (!stillValid) {
        $customSelect.val('');
    }
}

function filterTicketCustomStatusOptions(scope) {
    filterTicketMappedOptions(scope ? $(scope) : $(document), '.ticket-status-select', '.ticket-custom-status-select');
}

function filterTicketCustomPriorityOptions(scope) {
    filterTicketMappedOptions(scope ? $(scope) : $(document), '.ticket-priority-select', '.ticket-custom-priority-select');
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableTickets.init();

    // Search
    $('#ticketSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#ticketCategoryFilter, #ticketStatusFilter, #priorityFilter, #overdueFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Record First Response quick action
    $(document).on('click', '.ticket-record-response-btn', function () {
        var $btn = $(this);

        if (!confirm('Record the first response as happening right now?')) {
            return;
        }

        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            success: function () {
                dataTableInstance.ajax.reload(null, false);
            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong.';
                alert(message);
            }
        });
    });

    // Escalate quick action
    $(document).on('click', '.ticket-escalate-btn', function () {
        var $btn = $(this);

        if (!confirm('Escalate this ticket per the matching escalation rule?')) {
            return;
        }

        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            success: function () {
                dataTableInstance.ajax.reload(null, false);
            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong.';
                alert(message);
            }
        });
    });

    // Narrow Custom Status / Custom Priority options inside the remote modal
    $(document).on('change', '.ticket-status-select', function () {
        filterTicketCustomStatusOptions($(this).closest('form'));
    });
    $(document).on('change', '.ticket-priority-select', function () {
        filterTicketCustomPriorityOptions($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            filterTicketCustomStatusOptions('#modal_remote .modal-content');
            filterTicketCustomPriorityOptions('#modal_remote .modal-content');
        }).observe(modalContent, { childList: true, subtree: true });
    }

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
