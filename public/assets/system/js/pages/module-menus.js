var dataTableInstance;

var DataTableModuleMenus = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#menuTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#menuTable').data('url'),
                data: function (d) {
                    d.search = $('#menuSearch').val();
                    d.module_id = $('#moduleFilter').val();
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
                { data: 'module_name' },
                { data: 'label' },
                { data: 'name' },
                { data: 'parent_label' },
                { data: 'permission', defaultContent: '-' },
                { data: 'order' },
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
                        <p class="text-muted mb-0">No menus available</p>
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
                bindActionButtons();
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
// Action Buttons
// =====================================================
function bindActionButtons() {
    // Toggle status
    $('button.toggle-status').off('click').on('click', function (e) {
        e.preventDefault();
        var btn = $(this);
        var url = btn.data('url');
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (response.status) {
                    dataTableInstance.draw();
                } else {
                    alert('Failed: ' + (response.message || 'Unknown error'));
                }
            },
            error: function (xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
            }
        });
    });
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableModuleMenus.init();

    // Search
    $('#menuSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Module filter
    $('#moduleFilter').on('change', function () {
        dataTableInstance.draw();
        // Also update URL parameter for consistency (optional)
        var moduleId = $(this).val();
        var url = new URL(window.location.href);
        url.searchParams.set('module_id', moduleId);
        window.history.replaceState({}, '', url);
    });

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

    // Offcanvas: populate parent dropdown when module changes
    $(document).on('change', '#module_id', function () {
        var moduleId = $(this).val();
        var parentSelect = $('#parent_id');
        parentSelect.html('<option value="">None</option>');
        if (moduleId) {
            $.get("/admin/module-menus/by-module?module_id=" + moduleId, function (menus) {
                $.each(menus, function (i, menu) {
                    parentSelect.append('<option value="' + menu.id + '">' + menu.label + '</option>');
                });
                // If editing, set selected parent (we'll handle in edit load)
            });
        }
    });

    // When loading edit form, pre-select parent if any
    $(document).on('offcanvas.loaded', function (e, content) {
        // Assuming edit form has a hidden input or we can detect
        var parentId = $(content).find('#parent_id').data('selected');
        if (parentId) {
            $('#parent_id').val(parentId);
        }
        // Also trigger module change to populate parents after module selected
        var moduleId = $('#module_id').val();
        if (moduleId) {
            $('#module_id').trigger('change');
        }
    });

});
