var dataTableInstance;

var DataTableContacts = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#contactTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#contactTable').data('url'),
                data: function (d) {
                    d.search = $('#contactSearch').val();
                    applyContactAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'name' },
                { data: 'contact' },
                { data: 'company_name', defaultContent: '-' },
                { data: 'lead_name', defaultContent: '-' },
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
                        <p class="text-muted mb-0">No contacts available</p>
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
            initContactAdvSearch();
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
    DataTableContacts.init();

    // Search
    $('#contactSearch').on('keyup', function () {
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

var contactAdvFilters = {};

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

function initContactAdvSearch() {
    if (!$('#contactAdvSearchModal').length) return;
    $('.as-select').select2({
        width: '100%',
        dropdownParent: $('#contactAdvSearchModal'),
        templateResult: _selectOptionTemplate
    });
    $('#advSearchApply').on('click', function () { applyContactAdvFilters(true); });
    $('#advSearchReset').on('click', resetContactAdvFieldsUI);
    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key');
        delete contactAdvFilters[key];
        clearContactAdvField(key);
        renderContactFilterChips();
        dataTableInstance.draw();
    });
    $(document).on('click', '#advClearAllChips', function () {
        contactAdvFilters = {};
        resetContactAdvFieldsUI();
        renderContactFilterChips();
        dataTableInstance.draw();
    });
}

function resetContactAdvFieldsUI() {
    $('#advLead, #advHasCompany, #advStatus').val('').trigger('change.select2');
}

function clearContactAdvField(key) {
    var fields = { lead_id: '#advLead', has_company: '#advHasCompany', status: '#advStatus' };
    $(fields[key]).val('').trigger('change.select2');
}

function collectContactAdvFilters() {
    var filters = {};
    [
        ['lead_id', '#advLead', 'Lead'],
        ['has_company', '#advHasCompany', 'Company'],
        ['status', '#advStatus', 'Status']
    ].forEach(function (item) {
        var $field = $(item[1]);
        if ($field.val() !== '') filters[item[0]] = { value: $field.val(), label: item[2] + ': ' + $field.find('option:selected').text() };
    });
    return filters;
}

function renderContactFilterChips() {
    var $bar = $('#advSearchChipsBar'), $chips = $('#advSearchChips'), keys = Object.keys(contactAdvFilters);
    $chips.empty();
    if (!keys.length) { $bar.hide(); $('#advSearchBadge').hide(); return; }
    keys.forEach(function (key) {
        var filter = contactAdvFilters[key];
        $chips.append($('<span class="adv-chip"></span>').append($('<span></span>').text(filter.label)).append($('<button type="button" class="adv-chip-remove"><i class="ri-close-line"></i></button>').attr('data-key', key)));
    });
    $chips.append('<button type="button" class="adv-chip-clear-all" id="advClearAllChips"><i class="ri-close-circle-line"></i> Clear all</button>');
    $bar.show();
    $('#advSearchBadge').text(keys.length).show();
}

function applyContactAdvFilters(closeModal) {
    contactAdvFilters = collectContactAdvFilters();
    renderContactFilterChips();
    dataTableInstance.draw();
    if (closeModal && typeof bootstrap !== 'undefined') {
        var instance = bootstrap.Modal.getInstance(document.getElementById('contactAdvSearchModal'));
        if (instance) instance.hide();
    }
}

function applyContactAdvFiltersToRequest(d) {
    Object.keys(contactAdvFilters).forEach(function (key) { d[key] = contactAdvFilters[key].value; });
}
