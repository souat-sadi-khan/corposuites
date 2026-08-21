var dataTableInstance;

var DataTableWorkflowNotificationTriggers = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#workflowNotificationTriggerTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#workflowNotificationTriggerTable').data('url'),
                data: function (d) {
                    d.search = $('#workflowNotificationTriggerSearch').val();
                    var workflowDefinitionId = $('#workflowNotificationTriggerTable').data('workflow-definition-id');
                    if (workflowDefinitionId) {
                        d.workflow_definition_id = workflowDefinitionId;
                    }
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
                { data: 'event_badge' },
                { data: 'notify' },
                { data: 'message' },
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
                        <p class="text-muted mb-0">No notification triggers available</p>
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
// Conditional Notify Target field (role/user show a
// target select; initiator/approver need no target)
// =====================================================
function notifyOptionsHtml(form, type) {
    return form.find('.notification-trigger-notify-options[data-type="' + type + '"]').html();
}

function toggleNotifyIdField(form) {
    var type = form.find('.notification-trigger-notify-type').val();
    var wrap = form.find('.notification-trigger-notify-id-wrap');
    var select = wrap.find('.notification-trigger-notify-id');

    if (type === 'role' || type === 'user') {
        wrap.show();
        select.html(notifyOptionsHtml(form, type));
        select.prop('disabled', false);
    } else {
        wrap.hide();
        select.val('').prop('disabled', true);
    }
}

$(document).on('change', '.notification-trigger-notify-type', function () {
    toggleNotifyIdField($(this).closest('form'));
});

// Initialize conditional field state when the modal form is injected.
(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        $(modalContent).find('form').each(function () {
            toggleNotifyIdField($(this));
        });
    }).observe(modalContent, { childList: true, subtree: true });
})();

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
    DataTableWorkflowNotificationTriggers.init();

    // Search
    $('#workflowNotificationTriggerSearch').on('keyup', function () {
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
