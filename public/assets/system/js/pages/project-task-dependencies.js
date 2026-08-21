var dataTableInstance;

var DataTableProjectTaskDependencies = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#dependencyTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#dependencyTable').data('url'),
                data: function (d) {
                    d.search = $('#dependencySearch').val();
                    d.project_id = $('#projectFilter').val();
                    d.dependency_type = $('#dependencyTypeFilter').val();
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
                { data: 'project_name' },
                { data: 'link_col' },
                { data: 'type_badge' },
                { data: 'lag_label' },
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
                        <p class="text-muted mb-0">No task dependencies available</p>
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
// Predecessor / Successor options follow the selected project —
// the Form Request rejects a cross-project pair, so the form does
// not offer one in the first place. The plain "Project" select
// here is a client-side-only helper, never submitted.
// =====================================================
function filterDependencyTaskOptions(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $project = $scope.find('.ptd-project-select');

    if (!$project.length) {
        return;
    }

    var projectId = $project.val();

    $scope.find('.ptd-task-select').each(function () {
        var $select = $(this);
        var current = $select.val();
        var currentStillValid = !projectId;

        $select.find('option[data-project-id]').each(function () {
            var option = $(this);
            var matches = !projectId || option.attr('data-project-id') === projectId;
            option.prop('disabled', !matches).toggle(matches);

            if (matches && option.val() === current) {
                currentStillValid = true;
            }
        });

        if (current && !currentStillValid) {
            $select.val('');
        }
    });
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectTaskDependencies.init();

    // Search
    $('#dependencySearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#projectFilter, #dependencyTypeFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional fields inside the remote modal
    $(document).on('change', '.ptd-project-select', function () {
        filterDependencyTaskOptions($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            filterDependencyTaskOptions('#modal_remote .modal-content');
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
