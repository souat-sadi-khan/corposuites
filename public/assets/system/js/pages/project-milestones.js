var dataTableInstance;

var DataTableProjectMilestones = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#milestoneTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            ordering: false,
            ajax: {
                url: $('#milestoneTable').data('url'),
                data: function (d) {
                    d.search = $('#milestoneSearch').val();
                    d.project_id = $('#projectFilter').val();
                    d.milestone_status = $('#milestoneStatusFilter').val();
                    d.assigned_to = $('#ownerFilter').val();
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
                { data: 'name_col' },
                { data: 'project_name' },
                { data: 'owner' },
                { data: 'due_col' },
                { data: 'milestone_status_badge' },
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
                        <p class="text-muted mb-0">No milestones available</p>
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
// Completed Date only applies to a completed milestone —
// mirrors what the service derives server-side.
// =====================================================
function toggleMilestoneCompletedField(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $select = $scope.find('.milestone-status-select');

    if (!$select.length) {
        return;
    }

    var $completed = $scope.find('.milestone-completed-field');

    if ($select.val() === 'completed') {
        $completed.show();
    } else {
        $completed.hide();
    }
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectMilestones.init();

    // Search
    $('#milestoneSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#projectFilter, #milestoneStatusFilter, #ownerFilter, #overdueFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional Completed Date field inside the remote modal
    $(document).on('change', '.milestone-status-select', function () {
        toggleMilestoneCompletedField($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            toggleMilestoneCompletedField('#modal_remote .modal-content');
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
