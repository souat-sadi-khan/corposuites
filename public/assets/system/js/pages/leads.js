var dataTableInstance;

var DataTableLeads = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#leadTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#leadTable').data('url'),
                data: function (d) {
                    d.search = $('#leadSearch').val();
                    applyLeadAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
                { data: 'contact' },
                { data: 'lead_source_name', defaultContent: '-' },
                { data: 'lead_status_badge', defaultContent: '-' },
                { data: 'assigned_to_name', defaultContent: 'Unassigned' },
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
                        <p class="text-muted mb-0">No leads available</p>
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
            initLeadAdvSearch();
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
    DataTableLeads.init();

    // Search
    $('#leadSearch').on('keyup', function () {
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

var leadAdvFilters = {};

function _selectOptionTemplate(option) {
    if (!option.id || !option.element) {
        return option.text;
    }

    var $option = $(option.element);

    var logo = $option.attr('data-logo');
    var desc = $option.attr('data-desc');

    var $opt = $('<div class="sel-opt-rich"></div>');

    // Avatar
    if (logo) {
        $opt.append(
            '<div class="sel-opt-rich-avatar">' +
                '<img class="sel-opt-rich-img" src="' + logo + '" alt="">' +
            '</div>'
        );
    }

    // Info
    var $info = $('<div class="sel-opt-rich-info"></div>');

    $info.append(
        $('<div class="sel-opt-rich-name"></div>').text(option.text)
    );

    if (desc) {
        $info.append(
            $('<div class="sel-opt-rich-desc"></div>').text(desc)
        );
    }

    $opt.append($info);

    return $opt;
}

function initLeadAdvSearch() {
    if (!$('#leadAdvSearchModal').length) return;
    $('.as-select').select2({
        width: '100%',
        dropdownParent: $('#leadAdvSearchModal'),
        templateResult: _selectOptionTemplate
    });
    $('#advSearchApply').on('click', function () { applyLeadAdvFilters(true); });
    $('#advSearchReset').on('click', resetLeadAdvFieldsUI);
    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete leadAdvFilters[key];
        clearLeadAdvField(key);
        renderLeadFilterChips();
        dataTableInstance.draw();
    });
    $(document).on('click', '#advClearAllChips', function () {
        leadAdvFilters = {};
        resetLeadAdvFieldsUI();
        renderLeadFilterChips();
        dataTableInstance.draw();
    });
}

function resetLeadAdvFieldsUI() {
    $('#advLeadSource, #advLeadStatus, #advAssignedTo, #advStatus').val('').trigger('change.select2');
}

function clearLeadAdvField(key) {
    var fields = { lead_source_id: '#advLeadSource', lead_status_id: '#advLeadStatus', assigned_to: '#advAssignedTo', status: '#advStatus' };
    $(fields[key]).val('').trigger('change.select2');
}

function collectLeadAdvFilters() {
    var filters = {};
    [
        ['lead_source_id', '#advLeadSource', 'Source'],
        ['lead_status_id', '#advLeadStatus', 'Stage'],
        ['assigned_to', '#advAssignedTo', 'Assigned To'],
        ['status', '#advStatus', 'Status']
    ].forEach(function (item) {
        var $field = $(item[1]);
        if ($field.val() !== '') filters[item[0]] = { value: $field.val(), label: item[2] + ': ' + $field.find('option:selected').text() };
    });
    return filters;
}

function renderLeadFilterChips() {
    var $bar = $('#advSearchChipsBar'), $chips = $('#advSearchChips'), keys = Object.keys(leadAdvFilters);
    $chips.empty();
    if (!keys.length) { $bar.hide(); $('#advSearchBadge').hide(); return; }
    keys.forEach(function (key) {
        var filter = leadAdvFilters[key];
        $chips.append($('<span class="adv-chip"></span>').append($('<span></span>').text(filter.label)).append($('<button type="button" class="adv-chip-remove"><i class="ri-close-line"></i></button>').attr('data-key', key)));
    });
    $chips.append('<button type="button" class="adv-chip-clear-all" id="advClearAllChips"><i class="ri-close-circle-line"></i> Clear all</button>');
    $bar.show();
    $('#advSearchBadge').text(keys.length).show();
}

function applyLeadAdvFilters(closeModal) {
    leadAdvFilters = collectLeadAdvFilters();
    renderLeadFilterChips();
    dataTableInstance.draw();
    if (closeModal && typeof bootstrap !== 'undefined') {
        var instance = bootstrap.Modal.getInstance(document.getElementById('leadAdvSearchModal'));
        if (instance) instance.hide();
    }
}

function applyLeadAdvFiltersToRequest(d) {
    Object.keys(leadAdvFilters).forEach(function (key) { d[key] = leadAdvFilters[key].value; });
}
