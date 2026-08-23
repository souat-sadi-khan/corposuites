var dataTableInstance;

var DataTableRelationshipHistories = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#relationshipHistoryTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#relationshipHistoryTable').data('url'),
                data: function (d) {
                    d.search = $('#relationshipHistorySearch').val();
                    applyRelationshipHistoryAdvFiltersToRequest(d);
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'subject' },
                { data: 'related_to', defaultContent: '-' },
                { data: 'interaction_date_formatted', defaultContent: '-' },
                { data: 'created_by_name', defaultContent: '-' },
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
                        <p class="text-muted mb-0">No relationship history entries available</p>
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
            initRelationshipHistoryAdvSearch();
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
    DataTableRelationshipHistories.init();

    // Search
    $('#relationshipHistorySearch').on('keyup', function () {
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

var relationshipHistoryAdvFilters = {};

function initRelationshipHistoryAdvSearch() {
    if (!$('#relationshipHistoryAdvSearchModal').length) return;
    $('.as-select').select2({ width: '100%', dropdownParent: $('#relationshipHistoryAdvSearchModal') });
    $('#advSearchApply').on('click', function () { applyRelationshipHistoryAdvFilters(true); });
    $('#advSearchReset').on('click', resetRelationshipHistoryAdvFieldsUI);
    $(document).on('click', '.adv-chip-remove', function () {
        var key = $(this).data('key'); delete relationshipHistoryAdvFilters[key]; clearRelationshipHistoryAdvField(key); renderRelationshipHistoryFilterChips(); dataTableInstance.draw();
    });
    $(document).on('click', '#advClearAllChips', function () { relationshipHistoryAdvFilters = {}; resetRelationshipHistoryAdvFieldsUI(); renderRelationshipHistoryFilterChips(); dataTableInstance.draw(); });
}

function resetRelationshipHistoryAdvFieldsUI() { $('#advType, #advRelatedType, #advStatus').val('').trigger('change.select2'); $('#advDateFrom, #advDateTo').val(''); }
function clearRelationshipHistoryAdvField(key) {
    var fields = { type: '#advType', related_type: '#advRelatedType', status: '#advStatus' };
    if (key === 'date_range') $('#advDateFrom, #advDateTo').val(''); else $(fields[key]).val('').trigger('change.select2');
}
function collectRelationshipHistoryAdvFilters() {
    var filters = {};
    [['type','#advType','Type'],['related_type','#advRelatedType','Related To'],['status','#advStatus','Status']].forEach(function (item) { var $field=$(item[1]); if ($field.val() !== '') filters[item[0]]={value:$field.val(),label:item[2]+': '+$field.find('option:selected').text()}; });
    var from=$('#advDateFrom').val(), to=$('#advDateTo').val();
    if (from || to) filters.date_range={value:{from:from,to:to},label:'Date: '+(from || '…')+' → '+(to || '…')};
    return filters;
}
function renderRelationshipHistoryFilterChips() {
    var $bar=$('#advSearchChipsBar'),$chips=$('#advSearchChips'),keys=Object.keys(relationshipHistoryAdvFilters); $chips.empty();
    if (!keys.length) { $bar.hide(); $('#advSearchBadge').hide(); return; }
    keys.forEach(function (key) { var filter=relationshipHistoryAdvFilters[key]; $chips.append($('<span class="adv-chip"></span>').append($('<span></span>').text(filter.label)).append($('<button type="button" class="adv-chip-remove"><i class="ri-close-line"></i></button>').attr('data-key',key))); });
    $chips.append('<button type="button" class="adv-chip-clear-all" id="advClearAllChips"><i class="ri-close-circle-line"></i> Clear all</button>'); $bar.show(); $('#advSearchBadge').text(keys.length).show();
}
function applyRelationshipHistoryAdvFilters(closeModal) { relationshipHistoryAdvFilters=collectRelationshipHistoryAdvFilters(); renderRelationshipHistoryFilterChips(); dataTableInstance.draw(); if(closeModal && typeof bootstrap!=='undefined'){var instance=bootstrap.Modal.getInstance(document.getElementById('relationshipHistoryAdvSearchModal'));if(instance)instance.hide();} }
function applyRelationshipHistoryAdvFiltersToRequest(d) { Object.keys(relationshipHistoryAdvFilters).forEach(function(key){if(key==='date_range'){if(relationshipHistoryAdvFilters[key].value.from)d.interaction_date_from=relationshipHistoryAdvFilters[key].value.from;if(relationshipHistoryAdvFilters[key].value.to)d.interaction_date_to=relationshipHistoryAdvFilters[key].value.to;}else d[key]=relationshipHistoryAdvFilters[key].value;}); }
