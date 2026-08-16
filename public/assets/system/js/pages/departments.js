var dataTableInstance;

var DataTableDepartments = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#departmentTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#departmentTable').data('url'),
                data: function (d) {
                    d.search = $('#departmentSearch').val();
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
                { data: 'employees' },
                { data: 'status_badge' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                emptyTable: `
                    <div class="text-center py-4">
                        <img src="${window.location.origin}/assets/images/nothing-to-show.png" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No departments available</p>
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

document.addEventListener('DOMContentLoaded', function () {
    DataTableDepartments.init();

    $('#departmentSearch').on('keyup', function () { dataTableInstance.draw(); });
    $('#tlPrev').on('click', function () { dataTableInstance.page('previous').draw('page'); });
    $('#tlNext').on('click', function () { dataTableInstance.page('next').draw('page'); });
    $('#tlFilterBtn').on('click', function (e) { e.stopPropagation(); $('#tlFilterDd').toggleClass('is-open'); });
    $('#tlFilterDd').on('click', function (e) { e.stopPropagation(); });
    $(document).on('click', function () { $('#tlFilterDd').removeClass('is-open'); });
    $('#tlFilterDd input').on('change', function () { dataTableInstance.draw(); });
});
