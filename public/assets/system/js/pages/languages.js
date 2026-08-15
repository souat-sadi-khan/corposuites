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

                        let checked = selectedIds.has(Number(data))
                            ? 'checked'
                            : '';

                        return `
                            <input
                                type="checkbox"
                                class="tl-row-chk"
                                data-id="${data}"
                                ${checked}>
                        `;

                    }
                },

                {
                    data: 'id',
                    name: 'id',
                    visible: false
                },

                {
                    data: 'language',
                    name: 'name'
                },

                {
                    data: 'code',
                    name: 'code'
                },

                {
                    data: 'direction',
                    name: 'direction'
                },

                {
                    data: 'status',
                    name: 'is_active'
                },

                {
                    data: 'default',
                    name: 'is_default'
                },

                {
                    data: 'updated_at',
                    name: 'updated_at'
                },

                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                }

            ],

            language: {
                emptyTable: `
                    <div class="text-center py-4">
                        <img src="${window.location.origin}/assets/images/nothing-to-show.png"
                             class="img-fluid mb-2"
                             style="max-width:150px">
                        <p class="text-muted mb-0">No data available</p>
                    </div>`
            },

            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                // _componentRemoteOffcanvasLoadAfterAjax();
                _componentRemoteModalLoadAfterAjax();

                syncCurrentPageSelection();
                updateTlInfo();
            }
        });
    };

    return {
        init: function () {
            _componentDataTableSelect();
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
    $('#bulkDeleteBtn').toggle(total > 0);
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    DataTableSelect.init();

    $('#notifSearch').on('keyup', function () {
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

    // Action 2: Bulk Delete Selected Items
    $('#bulkDeleteBtn').on('click', function () {
        if (selectedIds.size === 0) return;

        const url = $(this).data('url');
        if(!url) {
            console.error('Bulk delete URL not specified.');
            return false;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: `Are you sure you want to delete ${selectedIds.size} selected items?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: JSON.stringify({ ids: Array.from(selectedIds) }),
                    contentType: 'application/json',
                    success: function (res) {
                        if (res.success) {
                            selectedIds.clear();
                            updateSelectedChip();
                            dataTableInstance.draw();

                            Lobibox.notify('success', {
                                size: 'mini',
                                rounded: true,
                                position: 'bottom right',
                                icon: 'ri-checkbox-circle-line',
                                msg: 'Selected items deleted successfully!'
                            });
                        } else {
                            Lobibox.notify('error', {
                                size: 'mini',
                                position: 'top right',
                                icon: 'ri-close-circle-line',
                                msg: res.message || 'Failed to delete items.'
                            });
                        }
                    },
                    error: function() {
                        Lobibox.notify('error', {
                            size: 'mini',
                            position: 'top right',
                            icon: 'ri-close-circle-line',
                            msg: 'An error occurred while processing your request.'
                        });
                    }
                });
            }
        });
    });

    // Action 3: Global Clear All
    $('#deleteAllBtn').on('click', function () {
        Swal.fire({
            title: 'Clear History completely?',
            text: "Clear all notification history completely? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, clear all!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/api/notifications/delete-all',
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (res) {
                        if (res.success) {
                            selectedIds.clear();
                            updateSelectedChip();
                            dataTableInstance.draw();

                            Lobibox.notify('success', {
                                size: 'mini',
                                rounded: true,
                                position: 'bottom right',
                                icon: 'ri-checkbox-circle-line',
                                msg: 'Notification history cleared successfully!'
                            });
                        } else {
                            Lobibox.notify('error', {
                                size: 'mini',
                                position: 'top right',
                                icon: 'ri-close-circle-line',
                                msg: res.message || 'Failed to clear history.'
                            });
                        }
                    },
                    error: function() {
                        Lobibox.notify('error', {
                            size: 'mini',
                            position: 'top right',
                            icon: 'ri-close-circle-line',
                            msg: 'An error occurred while processing your request.'
                        });
                    }
                });
            }
        });
    });
});
