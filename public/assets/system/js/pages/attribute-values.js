var dataTableInstance;

var DataTableAttributeValues = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#attributeValueTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#attributeValueTable').data('url'),
                data: function (d) {
                    d.search = $('#attributeValueSearch').val();
                    d.product_attribute_id = $('#attributeFilter').val();
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
                { data: 'value' },
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
                        <p class="text-muted mb-0">No attribute values available</p>
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
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableAttributeValues.init();

    // Search
    $('#attributeValueSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Attribute filter
    $('#attributeFilter').on('change', function () {
        dataTableInstance.draw();
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
});
