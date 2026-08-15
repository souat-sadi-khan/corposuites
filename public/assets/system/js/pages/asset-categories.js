var dataTableInstance;

var DataTableAssetCategories = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#assetCategoryTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#assetCategoryTable').data('url'),
                data: function (d) {
                    d.search = $('#assetCategorySearch').val();
                    d.depreciation_method = $('#depreciationMethodFilter').val();
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
                { data: 'name' },
                { data: 'depreciation_badge' },
                { data: 'useful_life_label' },
                { data: 'salvage_label' },
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
                        <p class="text-muted mb-0">No asset categories available</p>
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
// Useful Life visibility follows the depreciation method
// (mirrors the Form Request's required_unless rule so the
// field is not shown when it cannot apply)
// =====================================================
function toggleAssetCategoryLifeField(scope) {
    var $scope = scope ? $(scope) : $(document);
    var $select = $scope.find('.asset-category-depreciation');

    if (!$select.length) {
        return;
    }

    var $life = $scope.find('.asset-category-life');

    if ($select.val() === 'none') {
        $life.hide();
    } else {
        $life.show();
    }
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableAssetCategories.init();

    // Search
    $('#assetCategorySearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Depreciation method filter
    $('#depreciationMethodFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional Useful Life field inside the remote modal
    $(document).on('change', '.asset-category-depreciation', function () {
        toggleAssetCategoryLifeField($(this).closest('form'));
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            toggleAssetCategoryLifeField('#modal_remote .modal-content');
        }).observe(modalContent, { childList: true, subtree: true });
    }

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
