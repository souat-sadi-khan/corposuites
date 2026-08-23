var dataTableInstance;

var DataTableCompanies = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#companyTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#companyTable').data('url'),
                data: function (d) {
                    d.search = $('#companySearch').val();
                    applyCompanyAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
                { data: 'contact' },
                { data: 'website', defaultContent: '-' },
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
                        <p class="text-muted mb-0">No companies available</p>
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
            initCompanyAdvSearch();
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
    DataTableCompanies.init();

    // Search
    $('#companySearch').on('keyup', function () {
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

var companyAdvFilters = {};

function initCompanyAdvSearch() {
    if (!$('#companyAdvSearchModal').length) return;
    $('.as-select').select2({ width: '100%', dropdownParent: $('#companyAdvSearchModal') });
    $('#advSearchApply').on('click', function () { applyCompanyAdvFilters(true); });
    $('#advSearchReset').on('click', resetCompanyAdvFieldsUI);
    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete companyAdvFilters[key];
        clearCompanyAdvField(key);
        renderCompanyFilterChips();
        dataTableInstance.draw();
    });
    $(document).on('click', '#advClearAllChips', function () {
        companyAdvFilters = {};
        resetCompanyAdvFieldsUI();
        renderCompanyFilterChips();
        dataTableInstance.draw();
    });
}

function resetCompanyAdvFieldsUI() { $('#advIndustry, #advHasWebsite, #advStatus').val('').trigger('change.select2'); }

function clearCompanyAdvField(key) {
    var fields = { industry: '#advIndustry', has_website: '#advHasWebsite', status: '#advStatus' };
    $(fields[key]).val('').trigger('change.select2');
}

function collectCompanyAdvFilters() {
    var filters = {};
    [
        ['industry', '#advIndustry', 'Industry'],
        ['has_website', '#advHasWebsite', 'Website'],
        ['status', '#advStatus', 'Status']
    ].forEach(function (item) {
        var $field = $(item[1]);
        if ($field.val() !== '') filters[item[0]] = { value: $field.val(), label: item[2] + ': ' + $field.find('option:selected').text() };
    });
    return filters;
}

function renderCompanyFilterChips() {
    var $bar = $('#advSearchChipsBar'), $chips = $('#advSearchChips'), keys = Object.keys(companyAdvFilters);
    $chips.empty();
    if (!keys.length) { $bar.hide(); $('#advSearchBadge').hide(); return; }
    keys.forEach(function (key) {
        var filter = companyAdvFilters[key];
        $chips.append($('<span class="adv-chip"></span>').append($('<span></span>').text(filter.label)).append($('<button type="button" class="adv-chip-remove"><i class="ri-close-line"></i></button>').attr('data-key', key)));
    });
    $chips.append('<button type="button" class="adv-chip-clear-all" id="advClearAllChips"><i class="ri-close-circle-line"></i> Clear all</button>');
    $bar.show();
    $('#advSearchBadge').text(keys.length).show();
}

function applyCompanyAdvFilters(closeModal) {
    companyAdvFilters = collectCompanyAdvFilters();
    renderCompanyFilterChips();
    dataTableInstance.draw();
    if (closeModal && typeof bootstrap !== 'undefined') {
        var instance = bootstrap.Modal.getInstance(document.getElementById('companyAdvSearchModal'));
        if (instance) instance.hide();
    }
}

function applyCompanyAdvFiltersToRequest(d) {
    Object.keys(companyAdvFilters).forEach(function (key) { d[key] = companyAdvFilters[key].value; });
}
