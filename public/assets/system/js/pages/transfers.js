var dataTableInstance;

var DataTableTransfers = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#transferTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#transferTable').data('url'),
                data: function (d) {
                    d.search = $('#transferSearch').val();
                    var employeeId = $('#transferTable').data('employee-id');
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
                { data: 'department_change' },
                { data: 'designation_change' },
                { data: 'transfer_date_formatted' },
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
                        <p class="text-muted mb-0">No transfer records available</p>
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
    DataTableTransfers.init();

    // Search
    $('#transferSearch').on('keyup', function () {
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
    let employee_id = $(this).val();

    if (!employee_id) {
        $('input[name="from_department"]').val('').prop('readonly', false);
        $('input[name="from_designation"]').val('').prop('readonly', false);
        return;
    }

    $.ajax({
        url: '/admin/employees/find/' + employee_id, 
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                var employee = response.data;

                $('input[name="from_department"]').val(employee.department_name).prop('readonly', true);
                $('input[name="from_designation"]').val(employee.designation_name).prop('readonly', true);
            } else {
                console.log(response.message);
                $('input[name="from_department"]').val('').prop('readonly', false);
                $('input[name="from_designation"]').val('').prop('readonly', false);
            }
        },
        error: function(xhr, status, error) {
            $('input[name="from_department"]').val('').prop('readonly', false);
            $('input[name="from_designation"]').val('').prop('readonly', false);
        }
    });
});

$(document).on('change', '#to_department', function() {
    let departmentId = $(this).val();
    let $designationSelect = $('#to_designation');

    if (!departmentId) {
        $designationSelect.html('<option value="">Select Department First</option>').trigger('change');
        return;
    }

    // AJAX কল
    $.ajax({
        url: '/admin/designations/by-department/' + departmentId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let options = '<option value="">Select Designation</option>';
            $.each(response, function(index, designation) {
                options += '<option data-desc="'+ designation.description +'" value="' + designation.id + '">' + designation.name + '</option>';
            });
            $designationSelect.html(options).trigger('change'); 
        },
        error: function(xhr, status, error) {
            alert('ডিজাইনেশন লোড করতে সমস্যা হয়েছে।');
        }
    });
});