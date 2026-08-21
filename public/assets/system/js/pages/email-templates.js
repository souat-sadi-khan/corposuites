var dataTableInstance;
var selectedIds = new Set();

var DataTableEmailTemplates = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#templateTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[1, 'asc']],
            ajax: {
                url: $('#templateTable').data('url'),
                data: function (d) {
                    d.search = $('#templateSearch').val();

                    var statuses = [];
                    $('#tlFilterDd input[type="checkbox"]:checked').each(function () {
                        statuses.push($(this).val());
                    });
                    if (statuses.length) {
                        d.status = statuses.join(',');
                    }

                    d.category = $('#categoryFilter').val();
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
                    data: 'name',
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
                { data: 'key' },
                { data: 'category' },
                { data: 'subject' },
                {
                    data: 'status',
                    orderable: false,
                    searchable: false
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
                    <img src="${window.location.origin}/assets/images/nothing-to-show.svg" class="img-fluid mb-2" style="max-width:150px">
                    <p class="text-muted mb-0">No templates found</p>
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
            _statusUpdate(); // global toggle handler
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
// Selection Handling
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

    $('#templateTable tbody .tl-row-chk').each(function () {
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
// Expand Row Details (template info)
// =====================================================
function renderDetail(row) {
    return `
    <div class="tl-detail">
        <div class="tl-detail-col">
            <h4>Description</h4>
            <div class="tl-detail-row">
                <i class="ri-file-text-line"></i>
                ${row.description || 'No description'}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>Variables</h4>
            <div class="tl-detail-row">
                <i class="ri-code-box-line"></i>
                ${row.variables_display || '-'}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>System</h4>
            <div class="tl-detail-row">
                <i class="ri-shield-check-line"></i>
                ${row.is_system ? 'Yes' : 'No'}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>Sort Order</h4>
            <div class="tl-detail-row">
                <i class="ri-sort-asc"></i>
                ${row.sort_order || 0}
            </div>
        </div>
        <div class="tl-detail-col">
            <h4>Created</h4>
            <div class="tl-detail-row">
                <i class="ri-time-line"></i>
                ${row.created_at ? new Date(row.created_at).toLocaleString() : '-'}
            </div>
        </div>
    </div>
    `;
}

// =====================================================
// Duplicate Action
// =====================================================
function handleDuplicate(url) {
    $.ajax({
        url: url,
        type: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
            if (res.status) {
                Lobibox.notify('success', { msg: res.message });
                if (res.goto) {
                    window.location.href = res.goto;
                } else {
                    dataTableInstance.ajax.reload(null, false);
                }
            } else {
                Lobibox.notify('error', { msg: res.message || 'Duplicate failed' });
            }
        },
        error: function () {
            Lobibox.notify('error', { msg: 'Something went wrong' });
        }
    });
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableEmailTemplates.init();

    // Search
    $('#templateSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Pagination
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    // Row checkbox
    $('#templateTable tbody').on('change', '.tl-row-chk', function () {
        var id = Number($(this).data('id'));
        if (this.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        $(this).closest('tr').toggleClass('tl-row-selected', this.checked);
        updateSelectedChip();
    });

    // Select all
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

    // Expand row
    $('#templateTable tbody').on('click', '.tl-expand-btn', function () {
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

    // Filter dropdown toggle
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

    // Filter changes
    $('#tlFilterDd input[type="checkbox"]').on('change', function () {
        dataTableInstance.draw();
    });
    $('#categoryFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Duplicate button
    $(document).on('click', '#duplicate_item', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Duplicate Template?',
            text: 'This will create a copy of the template.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, duplicate!'
        }).then((result) => {
            if (result.isConfirmed) {
                handleDuplicate(url);
            }
        });
    });

});

// =====================================================
// Optional: refresh after modal close
// =====================================================
$(document).on('hidden.bs.modal', '#globalModal', function () {
    if (dataTableInstance) {
        dataTableInstance.ajax.reload(null, false);
    }
});
