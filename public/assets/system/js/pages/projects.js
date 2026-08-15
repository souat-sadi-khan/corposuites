var dataTableInstance;

var DataTableProjects = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#projectTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#projectTable').data('url'),
                data: function (d) {
                    d.search = $('#projectSearch').val();
                    d.client_id = $('#clientFilter').val();
                    d.project_status = $('#projectStatusFilter').val();
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
                { data: 'name' },
                { data: 'client_name' },
                { data: 'manager_col' },
                { data: 'timeline' },
                { data: 'progress_col' },
                { data: 'priority_badge' },
                { data: 'project_status_badge' },
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
                        <p class="text-muted mb-0">No projects available</p>
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
// Actual End Date only applies to a completed project, and a
// completed project is 100% done - mirrors what the service
// derives server-side so the form never disagrees with it.
// =====================================================
function toggleProjectCompletionFields(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $select = $scope.find('.project-status-select');

    if (!$select.length) {
        return;
    }

    var completed = $select.val() === 'completed';
    var $actualEnd = $scope.find('.project-actual-end-field');
    var $progress = $scope.find('.project-progress-field');

    if (completed) {
        $actualEnd.show();
        $progress.find('input[name="progress_percent"]').val(100);
        $progress.hide();
    } else {
        $actualEnd.hide();
        $progress.show();
    }
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjects.init();

    // Search
    $('#projectSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#clientFilter, #projectStatusFilter, #priorityFilter, #overdueFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional completion fields inside the remote modal
    $(document).on('change', '.project-status-select', function () {
        toggleProjectCompletionFields($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            toggleProjectCompletionFields('#modal_remote .modal-content');
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
