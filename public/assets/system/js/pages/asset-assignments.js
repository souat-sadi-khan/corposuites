var dataTableInstance;

var DataTableAssetAssignments = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#assetAssignmentTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#assetAssignmentTable').data('url'),
                data: function (d) {
                    d.search = $('#assetAssignmentSearch').val();
                    d.employee_id = $('#employeeFilter').val();
                    d.assignment_status = $('#assignmentStatusFilter').val();
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
                { data: 'asset_name' },
                { data: 'employee_name' },
                { data: 'assigned_date_formatted' },
                { data: 'return_info' },
                { data: 'assignment_status_badge' },
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
                        <p class="text-muted mb-0">No asset assignments recorded yet</p>
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
// Return fields only apply once the asset comes back
// (mirrors the Form Request's required_if rule)
// =====================================================
function toggleAssignmentReturnFields(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $select = $scope.find('.assignment-status-select');

    if (!$select.length) {
        return;
    }

    var $fields = $scope.find('.assignment-return-field');

    if ($select.val() === 'assigned') {
        $fields.hide();
    } else {
        $fields.show();
    }
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableAssetAssignments.init();

    // Search
    $('#assetAssignmentSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#employeeFilter, #assignmentStatusFilter, #overdueFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional return fields inside the remote modal
    $(document).on('change', '.assignment-status-select', function () {
        toggleAssignmentReturnFields($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            toggleAssignmentReturnFields('#modal_remote .modal-content');
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
