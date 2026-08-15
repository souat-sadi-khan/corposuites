var dataTableInstance;
var selectedIds = new Set();

var DataTableSender = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#senderTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'asc']],
            ajax: {
                url: $('#senderTable').data('url'),
                data: function (d) {
                    d.search = $('#senderSearch').val();
                    var defaults = [];

                    $('#tlFilterDd input:checked').each(function () {
                        defaults.push($(this).val());
                    });

                    if (defaults.length) {
                        d.is_default = defaults.join(',');
                    }
                }
            },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'tl-check-col',
                    render: function (data) {
                        var checked = selectedIds.has(Number(data)) ? 'checked' : '';
                        return `<input type="checkbox" class="tl-row-chk" data-id="${data}" ${checked}>`;
                    }
                },
                {
                    data: 'provider',
                    name: 'provider',
                },
                {
                    data: 'full_name',
                    render: function (data, type, row) {
                        return `
                        <div class="tl-name-cell">
                            <button class="tl-expand-btn" title="Expand">
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                            <span class="tl-name-txt">${data}</span>
                        </div>
                        `;
                    }
                },
                {
                    data: 'is_default'
                },
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
                    <p class="text-muted mb-0">No sender identities found</p>
                </div>
                `
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                syncCurrentPageSelection();
                updateTlInfo();
                _componentSwitch();
                if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
                    _componentRemoteModalLoadAfterAjax();
                }
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
// Selected Checkbox
// =====================================================
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

    $('#senderTable tbody .tl-row-chk').each(function () {
        var id = Number($(this).data('id'));
        var checked = selectedIds.has(id);
        $(this).prop('checked', checked);
        $(this).closest('tr').toggleClass('tl-row-selected', checked);
    });

    var allChecked = currentIds.length > 0 && currentIds.every(function (id) {
        return selectedIds.has(id);
    });

    $('#selectAllChk').prop('checked', allChecked);
}

// =====================================================
// Expand Row Details (optional)
// =====================================================
function renderDetail(row) {
    return `
    <div class="tl-detail">
        <div class="tl-detail-col">
            <h4>Email</h4>
            <div class="tl-detail-row">
                <i class="ri-mail-line"></i>
                ${row.email}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>Name</h4>
            <div class="tl-detail-row">
                <i class="ri-user-line"></i>
                ${row.name || '-'}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>Default</h4>
            <div class="tl-detail-row">
                <i class="ri-star-line"></i>
                ${row.is_default ? 'Yes' : 'No'}
            </div>
        </div>
    </div>
    `;
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableSender.init();

    // Search
    $('#senderSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });

    // Next
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    // Row Checkbox
    $('#senderTable tbody').on('change', '.tl-row-chk', function () {
        var id = Number($(this).data('id'));
        if (this.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        $(this).closest('tr').toggleClass('tl-row-selected', this.checked);
        updateSelectedChip();
    });

    // Select All Current Page
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

    // Expand Row
    $('#senderTable tbody').on('click', '.tl-expand-btn', function () {
        var btn = $(this);
        var tr = btn.closest('tr');
        var row = dataTableInstance.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('tl-row-expanded');
            btn.removeClass('is-open');
        } else {
            row.child(renderDetail(row.data())).show();
            tr.addClass('tl-row-expanded');
            btn.addClass('is-open');
        }
    });

    // Filter Dropdown
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

    // =====================================================
    // Custom Actions for Sender Identities
    // =====================================================

    $(document).on('click', '.set-default-sender', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        var providerId = $(this).data('provider');
        var url = '/admin/email/sender-identities/' + id + '/default';

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to set this sender identity as default?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, set default!'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                method: 'PUT',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',

                success: function (response) {
                    if (response.success) {
                        Lobibox.notify('success', {
                            msg: response.message
                        });

                        if (dataTableInstance) {
                            dataTableInstance.ajax.reload(null, false);   // same as provider’s refresh
                        }
                    } else {
                        Lobibox.notify('error', {
                            msg: response.message
                        });
                    }
                },

                error: function () {
                    Lobibox.notify('error', {
                        msg: 'Failed to set default sender identity.'
                    });
                }
            });
        });
    });
});
