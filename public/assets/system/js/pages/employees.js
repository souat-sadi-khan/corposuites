var dataTableInstance;

var DataTableEmployees = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#employeeTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#employeeTable').data('url'),
                data: function (d) {
                    d.search = $('#employeeSearch').val();
                    $('#employeeAdvancedFilters .employee-filter').each(function () {
                        if ($(this).val()) {
                            d[$(this).attr('name')] = $(this).val();
                        }
                    });
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
                { data: 'contact' },
                { data: 'type_status' },
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
                        <p class="text-muted mb-0">No employees available</p>
                    </div>
                `
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateTlInfo();
                _componentSwitch();
                if (typeof _componentRemoteOffcanvasLoadAfterAjax === 'function') {
                    _componentRemoteOffcanvasLoadAfterAjax();
                }
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
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableEmployees.init();

    // Search
    $('#employeeSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous / Next
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    $('#employeeAdvancedFilterBtn').on('click', function () {
        $('#employeeAdvancedFilters').slideToggle(150);
    });
    $('#employeeAdvancedFilters .employee-filter').on('change', function () {
        dataTableInstance.draw();
    });
    $('#clearEmployeeFilters').on('click', function () {
        $('#employeeAdvancedFilters .employee-filter').val('');
        dataTableInstance.draw();
    });
});
