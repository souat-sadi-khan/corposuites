var dataTableInstance;

var DataTableLeadSources = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#leadSourceTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#leadSourceTable').data('url'),
                data: function (d) {
                    d.search = $('#leadSourceSearch').val();
                    applyLeadSourceAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
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
                        <p class="text-muted mb-0">No lead sources available</p>
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
            initLeadSourceAdvSearch();
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
    DataTableLeadSources.init();

    // Search
    $('#leadSourceSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous / Next
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

});

var leadSourceAdvFilters = {};

function initLeadSourceAdvSearch() {
    if (!$('#leadSourceAdvSearchModal').length) return;

    $('.as-select').select2({ width: '100%', dropdownParent: $('#leadSourceAdvSearchModal') });
    $('#advSearchApply').on('click', function () { applyLeadSourceAdvFilters(true); });
    $('#advSearchReset').on('click', resetLeadSourceAdvFieldsUI);

    $(document).on('click', '.adv-chip-remove', function () {
        delete leadSourceAdvFilters[$(this).data('key')];
        clearLeadSourceAdvField($(this).data('key'));
        renderLeadSourceFilterChips();
        dataTableInstance.draw();
    });
    $(document).on('click', '#advClearAllChips', function () {
        leadSourceAdvFilters = {};
        resetLeadSourceAdvFieldsUI();
        renderLeadSourceFilterChips();
        dataTableInstance.draw();
    });
}

function resetLeadSourceAdvFieldsUI() {
    $('#advStatus, #advHasDescription').val('').trigger('change.select2');
}

function clearLeadSourceAdvField(key) {
    key === 'status' ? $('#advStatus').val('').trigger('change.select2') : $('#advHasDescription').val('').trigger('change.select2');
}

function collectLeadSourceAdvFilters() {
    var filters = {};
    var $status = $('#advStatus');
    var $description = $('#advHasDescription');
    if ($status.val() !== '') filters.status = { value: $status.val(), label: 'Status: ' + $status.find('option:selected').text() };
    if ($description.val() !== '') filters.has_description = { value: $description.val(), label: 'Description: ' + $description.find('option:selected').text() };
    return filters;
}

function renderLeadSourceFilterChips() {
    var $bar = $('#advSearchChipsBar'), $chips = $('#advSearchChips'), keys = Object.keys(leadSourceAdvFilters);
    $chips.empty();
    if (!keys.length) { $bar.hide(); $('#advSearchBadge').hide(); return; }
    keys.forEach(function (key) {
        var filter = leadSourceAdvFilters[key];
        $chips.append($('<span class="adv-chip"></span>').append($('<span></span>').text(filter.label)).append($('<button type="button" class="adv-chip-remove"><i class="ri-close-line"></i></button>').attr('data-key', key)));
    });
    $chips.append('<button type="button" class="adv-chip-clear-all" id="advClearAllChips"><i class="ri-close-circle-line"></i> Clear all</button>');
    $bar.show();
    $('#advSearchBadge').text(keys.length).show();
}

function applyLeadSourceAdvFilters(closeModal) {
    leadSourceAdvFilters = collectLeadSourceAdvFilters();
    renderLeadSourceFilterChips();
    dataTableInstance.draw();
    if (closeModal && typeof bootstrap !== 'undefined') {
        var instance = bootstrap.Modal.getInstance(document.getElementById('leadSourceAdvSearchModal'));
        if (instance) instance.hide();
    }
}

function applyLeadSourceAdvFiltersToRequest(d) {
    if (leadSourceAdvFilters.status) d.status = leadSourceAdvFilters.status.value;
    if (leadSourceAdvFilters.has_description) d.has_description = leadSourceAdvFilters.has_description.value;
}
