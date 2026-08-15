var dataTableInstance;

var DataTableProjectTasks = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#taskTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#taskTable').data('url'),
                data: function (d) {
                    d.search = $('#taskSearch').val();
                    d.project_id = $('#projectFilter').val();
                    d.task_status = $('#taskStatusFilter').val();
                    d.priority = $('#priorityFilter').val();
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
                { data: 'title_col' },
                { data: 'project_name' },
                { data: 'owner' },
                { data: 'schedule' },
                { data: 'progress_col' },
                { data: 'priority_badge' },
                { data: 'task_status_badge' },
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
                        <p class="text-muted mb-0">No tasks available</p>
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
// Milestone options follow the selected project — the Form
// Request rejects a cross-project milestone, so the form does
// not offer one in the first place.
// =====================================================
function filterTaskMilestoneOptions(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $project = $scope.find('.task-project-select');
    var $milestone = $scope.find('.task-milestone-select');

    if (!$project.length || !$milestone.length) {
        return;
    }

    var projectId = $project.val();
    var current = $milestone.val();
    var currentStillValid = false;

    $milestone.find('option').each(function () {
        var option = $(this);
        var optionProject = option.attr('data-project-id');

        if (!optionProject) {
            return; // the "not tied to a milestone" option is always available
        }

        var matches = projectId && optionProject === projectId;
        option.prop('disabled', !matches).toggle(!!matches);

        if (matches && option.val() === current) {
            currentStillValid = true;
        }
    });

    if (current && !currentStillValid) {
        $milestone.val('');
    }
}

// =====================================================
// Completed Date and Progress follow the task state —
// mirrors what the service derives server-side.
// =====================================================
function toggleTaskCompletionFields(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $select = $scope.find('.task-status-select');

    if (!$select.length) {
        return;
    }

    var done = $select.val() === 'done';
    var $completed = $scope.find('.task-completed-field');
    var $progress = $scope.find('.task-progress-field');

    if (done) {
        $completed.show();
        $progress.find('input[name="progress_percent"]').val(100);
        $progress.hide();
    } else {
        $completed.hide();
        $progress.show();
    }
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectTasks.init();

    // Search
    $('#taskSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#projectFilter, #taskStatusFilter, #priorityFilter, #ownerFilter, #overdueFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional fields inside the remote modal
    $(document).on('change', '.task-status-select', function () {
        toggleTaskCompletionFields($(this).closest('form'));
    });

    $(document).on('change', '.task-project-select', function () {
        filterTaskMilestoneOptions($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            toggleTaskCompletionFields('#modal_remote .modal-content');
            filterTaskMilestoneOptions('#modal_remote .modal-content');
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
