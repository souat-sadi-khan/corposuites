var dataTableInstance;

var DataTableMaintenanceRecords = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#recordTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#recordTable').data('url'),
                data: function (d) {
                    d.search = $('#recordSearch').val();
                    d.asset_id = $('#recordAssetFilter').val();
                    d.origin = $('#originFilter').val();
                    d.record_status = $('#recordStatusFilter').val();
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
                { data: 'origin' },
                { data: 'type_label' },
                { data: 'performed_date_formatted' },
                { data: 'performed_by_col' },
                { data: 'cost_col' },
                { data: 'record_status_badge' },
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
                        <p class="text-muted mb-0">No maintenance history recorded yet</p>
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

function updateTlInfo() {
    var info = dataTableInstance.page.info();
    var start = info.recordsDisplay === 0 ? 0 : info.start + 1;
    $('#tlInfo').text(start + ' - ' + info.end + ' of ' + info.recordsDisplay);
    $('#tlPrev').prop('disabled', info.page === 0);
    $('#tlNext').prop('disabled', info.page >= info.pages - 1 || info.pages === 0);
}

// =====================================================
// Only offer schedules belonging to the selected asset —
// the Form Request enforces the same rule server-side.
// =====================================================
function filterRecordScheduleOptions(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $asset = $scope.find('.record-asset-select');
    var $schedule = $scope.find('.record-schedule-select');

    if (!$asset.length || !$schedule.length) {
        return;
    }

    var assetId = $asset.val();

    $schedule.find('option').each(function () {
        var owner = $(this).data('asset');

        if (!owner) {
            return; // the "Unplanned" option always stays
        }

        var matches = String(owner) === String(assetId);
        $(this).prop('disabled', !matches).toggle(matches);
    });

    var $selected = $schedule.find('option:selected');
    if ($selected.length && $selected.prop('disabled')) {
        $schedule.val('');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    DataTableMaintenanceRecords.init();

    $('#recordSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    $('#recordAssetFilter, #originFilter, #recordStatusFilter').on('change', function () {
        dataTableInstance.draw();
    });

    $(document).on('change', '.record-asset-select', function () {
        filterRecordScheduleOptions($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            filterRecordScheduleOptions('#modal_remote .modal-content');
        }).observe(modalContent, { childList: true, subtree: true });
    }

    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

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
