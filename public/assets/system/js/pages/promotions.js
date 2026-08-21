var dataTableInstance;

var DataTablePromotions = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#promotionTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#promotionTable').data('url'),
                data: function (d) {
                    d.search = $('#promotionSearch').val();
                    var employeeId = $('#promotionTable').data('employee-id');
                    if (employeeId) {
                        d.employee_id = employeeId;
                    }
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
                { data: 'employee_name' },
                { data: 'designation_change' },
                { data: 'salary_change' },
                { data: 'promotion_date_formatted' },
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
                        <p class="text-muted mb-0">No promotion records available</p>
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
    DataTablePromotions.init();

    // Search
    $('#promotionSearch').on('keyup', function () {
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

$(document).on('change', '#employee_id', function() {
    let employeeId = $(this).val();
    let $fromDesignation = $('input[name="from_designation"]');
    let $toDesignation = $('#to_designation');

    if (!employeeId) {
        $fromDesignation.val('').prop('readonly', false);
        $toDesignation.html('<option value="">Select Employee First</option>').trigger('change');
        return;
    }

    $.ajax({
        url: '/admin/employees/find/' + employeeId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                let employee = response.data;

                let designationName = employee.designation ? employee.designation.name : '';
                $fromDesignation.val(designationName).prop('readonly', true);

                let departmentId = employee.department ? employee.department.id : null;

                if (departmentId) {
                    $.ajax({
                        url: '/admin/designations/by-department/' + departmentId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(designations) {
                            let options = '<option value="">Select Designation</option>';
                            $.each(designations, function(index, des) {
                                options += '<option data-desc="'+ des.description +'" value="' + des.id + '">' + des.name + '</option>';
                            });
                            $toDesignation.html(options).trigger('change');
                        },
                        error: function() {
                            $toDesignation.html('<option value="">Error loading designations</option>').trigger('change');
                        }
                    });
                } else {
                    $toDesignation.html('<option value="">No Department Assigned</option>').trigger('change');
                }

            } else {
                console.log(response.message);
                $fromDesignation.val('').prop('readonly', false);
                $toDesignation.html('<option value="">Select Employee First</option>').trigger('change');
            }
        },
        error: function() {
            $fromDesignation.val('').prop('readonly', false);
            $toDesignation.html('<option value="">Select Employee First</option>').trigger('change');
        }
    });
});