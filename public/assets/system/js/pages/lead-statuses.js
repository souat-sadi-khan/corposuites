var dataTableInstance;

var DataTableLeadStatuses = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#leadStatusTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#leadStatusTable').data('url'),
                data: function (d) {
                    d.search = $('#leadStatusSearch').val();
                    applyLeadStatusAdvFiltersToRequest(d);
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
                        <p class="text-muted mb-0">No lead statuses available</p>
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
            initLeadStatusAdvSearch();
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
    DataTableLeadStatuses.init();

    // Search
    $('#leadStatusSearch').on('keyup', function () {
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

var leadStatusAdvFilters = {};

function initLeadStatusAdvSearch() {
    if (!$('#leadStatusAdvSearchModal').length) return;
    $('.as-select').select2({ width: '100%', dropdownParent: $('#leadStatusAdvSearchModal') });
    $('#advSearchApply').on('click', function () { applyLeadStatusAdvFilters(true); });
    $('#advSearchReset').on('click', resetLeadStatusAdvFieldsUI);
    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete leadStatusAdvFilters[key];
        clearLeadStatusAdvField(key);
        renderLeadStatusFilterChips();
        dataTableInstance.draw();
    });
    $(document).on('click', '#advClearAllChips', function () {
        leadStatusAdvFilters = {};
        resetLeadStatusAdvFieldsUI();
        renderLeadStatusFilterChips();
        dataTableInstance.draw();
    });
}

function resetLeadStatusAdvFieldsUI() {
    $('#advStatus, #advHasDescription').val('').trigger('change.select2');
}

function clearLeadStatusAdvField(key) {
    key === 'status' ? $('#advStatus').val('').trigger('change.select2') : $('#advHasDescription').val('').trigger('change.select2');
}

function collectLeadStatusAdvFilters() {
    var filters = {}, $status = $('#advStatus'), $description = $('#advHasDescription');
    if ($status.val() !== '') filters.status = { value: $status.val(), label: 'Status: ' + $status.find('option:selected').text() };
    if ($description.val() !== '') filters.has_description = { value: $description.val(), label: 'Description: ' + $description.find('option:selected').text() };
    return filters;
}

function renderLeadStatusFilterChips() {
    var $bar = $('#advSearchChipsBar'), $chips = $('#advSearchChips'), keys = Object.keys(leadStatusAdvFilters);
    $chips.empty();
    if (!keys.length) { $bar.hide(); $('#advSearchBadge').hide(); return; }
    keys.forEach(function (key) {
        var filter = leadStatusAdvFilters[key];
        $chips.append($('<span class="adv-chip"></span>').append($('<span></span>').text(filter.label)).append($('<button type="button" class="adv-chip-remove"><i class="ri-close-line"></i></button>').attr('data-key', key)));
    });
    $chips.append('<button type="button" class="adv-chip-clear-all" id="advClearAllChips"><i class="ri-close-circle-line"></i> Clear all</button>');
    $bar.show();
    $('#advSearchBadge').text(keys.length).show();
}

function applyLeadStatusAdvFilters(closeModal) {
    leadStatusAdvFilters = collectLeadStatusAdvFilters();
    renderLeadStatusFilterChips();
    dataTableInstance.draw();
    if (closeModal && typeof bootstrap !== 'undefined') {
        var instance = bootstrap.Modal.getInstance(document.getElementById('leadStatusAdvSearchModal'));
        if (instance) instance.hide();
    }
}

function applyLeadStatusAdvFiltersToRequest(d) {
    if (leadStatusAdvFilters.status) d.status = leadStatusAdvFilters.status.value;
    if (leadStatusAdvFilters.has_description) d.has_description = leadStatusAdvFilters.has_description.value;
}
