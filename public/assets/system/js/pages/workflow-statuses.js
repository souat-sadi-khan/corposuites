var dataTableInstance;

var DataTableWorkflowStatuses = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#workflowStatusTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[5, 'asc']],
            ajax: {
                url: $('#workflowStatusTable').data('url'),
                data: function (d) {
                    d.search = $('#workflowStatusSearch').val();
                    var workflowDefinitionId = $('#workflowStatusTable').data('workflow-definition-id');
                    if (workflowDefinitionId) {
                        d.workflow_definition_id = workflowDefinitionId;
                    } else if ($('#workflowStatusDefinitionFilter').length) {
                        var selected = $('#workflowStatusDefinitionFilter').val();
                        if (selected) {
                            d.workflow_definition_id = selected;
                        }
                    }
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'key_label' },
                { data: 'label' },
                { data: 'color_swatch' },
                { data: 'is_terminal_badge' },
                { data: 'sort_order' },
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
                        <p class="text-muted mb-0">No workflow statuses available</p>
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
    DataTableWorkflowStatuses.init();

    // Search
    $('#workflowStatusSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Workflow definition filter (only present when not already scoped via query param)
    $('#workflowStatusDefinitionFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Previous / Next
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });
});
