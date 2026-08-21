var dataTableInstance;
var selectedIds = new Set();

var DataTableSelect = function () {
    var _componentDataTableSelect = function () {
        if (!$().DataTable) {
            console.warn('Warning - datatables.min.js is not loaded.');
            return;
        }

        $.extend($.fn.dataTable.defaults, {
            autoWidth: false,
            responsive: true
        });

        dataTableInstance = $('#dataTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            order: [[1, 'desc']],
            lengthChange: false,
            searching: true,

            ajax: {
                url: $('#dataTable').data('url')
            },

            columns: [
                {
                    data: 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'tl-check-col',
                    render: function (data) {
                        var checked = selectedIds.has(Number(data)) ? 'checked' : '';
                        return '<input type="checkbox" class="tl-row-chk" data-id="' + data + '" ' + checked + '>';
                    }
                },
                {
                    data: 'id',
                    name: 'id',
                    visible: false
                },
                {
                    data: 'name',
                    name: 'name',
                    render: function (data) {
                        return '<span class="tl-name-txt">' + data + '</span>';
                    }
                },
                {
                    data: 'permissions',
                    name: 'permissions',
                },
                {
                    data: 'status',
                    name: 'status',
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-right',
                    render: function (data) {
                        return data;
                    }
                }
            ],

            language: {
                emptyTable: `
                    <div class="text-center py-4">
                        <img src="${window.location.origin}/assets/images/nothing-to-show.svg"
                             class="img-fluid mb-2"
                             style="max-width:150px">
                        <p class="text-muted mb-0">No data available</p>
                    </div>`
            },

            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                _componentRemoteOffcanvasLoadAfterAjax();
                _componentSwitch();
                _componentRemoteModalLoadAfterAjax();

                syncCurrentPageSelection();
                updateTlInfo();
            }
        });
    };

    return {
        init: function () {
            _componentDataTableSelect();
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

function updateSelectedChip() {
    var total = selectedIds.size;

    $('#tlSelectedChip').toggle(total > 0);
    $('#tlSelectedCount').text(total + ' Selected');

    syncCurrentPageSelection();
}

function syncCurrentPageSelection() {
    var currentIds = [];

    dataTableInstance.rows({ page: 'current' }).every(function () {
        currentIds.push(Number(this.data().id));
    });

    $('#dataTable tbody .tl-row-chk').each(function () {
        var id = Number($(this).data('id'));
        var checked = selectedIds.has(id);

        $(this).prop('checked', checked);
        $(this).closest('tr').toggleClass('tl-row-selected', checked);
    });

    var allCurrentChecked = currentIds.length > 0 && currentIds.every(function (id) {
        return selectedIds.has(id);
    });

    $('#selectAllChk').prop('checked', allCurrentChecked);
}

document.addEventListener('DOMContentLoaded', function () {
    DataTableSelect.init();

    $('#roleSearch').on('keyup', function () {
        dataTableInstance.search(this.value).draw();
    });

    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });

    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    $('#dataTable tbody').on('change', '.tl-row-chk', function () {
        var id = Number($(this).data('id'));

        if (this.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }

        $(this).closest('tr').toggleClass('tl-row-selected', this.checked);
        updateSelectedChip();
    });

    $('#selectAllChk').on('change', function () {
        var checked = this.checked;

        dataTableInstance.rows({ page: 'current' }).every(function () {
            var id = Number(this.data().id);

            if (checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });

        updateSelectedChip();
    });
});
