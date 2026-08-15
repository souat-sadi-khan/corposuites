var dataTableInstance;

var DataTableProjectTimeEntries = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#timeEntryTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#timeEntryTable').data('url'),
                data: function (d) {
                    d.search = $('#timeEntrySearch').val();
                    d.project_id = $('#projectFilter').val();
                    d.employee_id = $('#employeeFilter').val();
                    d.billable = $('#billableFilter').val();
                    d.running = $('#runningFilter').val();
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
                { data: 'project_col' },
                { data: 'work_date_formatted' },
                { data: 'duration_col' },
                { data: 'billable_badge' },
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
                        <p class="text-muted mb-0">No time entries available</p>
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
// Task options follow the selected project — the Form Request
// rejects a cross-project task, so the form does not offer one.
// Shared by both the manual entry form (.te-*) and the quick
// start-timer form (.ste-*).
// =====================================================
function filterTimeEntryTaskOptions(scope, projectClass, taskClass) {
    var $scope = scope ? $(scope) : $(document);
    var $project = $scope.find(projectClass);
    var $task = $scope.find(taskClass);

    if (!$project.length || !$task.length) {
        return;
    }

    var projectId = $project.val();
    var current = $task.val();
    var currentStillValid = !projectId;

    $task.find('option[data-project-id]').each(function () {
        var option = $(this);
        var matches = !projectId || option.attr('data-project-id') === projectId;
        option.prop('disabled', !matches).toggle(matches);

        if (matches && option.val() === current) {
            currentStillValid = true;
        }
    });

    if (current && !currentStillValid) {
        $task.val('');
    }
}

// =====================================================
// Hours vs clock times are mutually exclusive — a light UX nudge
// on top of the server-side rule, clearing whichever side the
// user isn't actively using.
// =====================================================
function bindTimeEntryDurationToggle(scope) {
    var $scope = scope ? $(scope) : $(document);

    $scope.find('.te-hours-input').off('input.teDuration').on('input.teDuration', function () {
        if ($(this).val()) {
            $scope.find('.te-clock-input').val('');
        }
    });

    $scope.find('.te-clock-input').off('input.teDuration').on('input.teDuration', function () {
        if ($(this).val()) {
            $scope.find('.te-hours-input').val('');
        }
    });
}

// =====================================================
// Start Timer — a plain custom submit (not .ajax-form), since a
// successful start changes the "My Timer" widget above the table,
// which is server-rendered and needs a full page reload to reflect.
// =====================================================
$(document).on('submit', '.start-timer-form', function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('#submit');
    var $submittingBtn = $form.find('#submitting');

    $submitBtn.hide();
    $submittingBtn.show();

    $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: function (res) {
            if (res.status) {
                window.location.reload();
            } else {
                $submitBtn.show();
                $submittingBtn.hide();
                alert(res.message || 'Unable to start the timer.');
            }
        },
        error: function (xhr) {
            $submitBtn.show();
            $submittingBtn.hide();
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to start the timer.';
            alert(msg);
        }
    });
});

// =====================================================
// Stop Timer — used both by the widget's button and the per-row
// action icon. Also reloads the page, for the same reason.
// =====================================================
$(document).on('click', '.time-entry-stop-btn', function () {
    var url = $(this).data('url');

    if (!confirm('Stop this timer? The hours worked will be calculated automatically.')) {
        return;
    }

    $.ajax({
        url: url,
        type: 'POST',
        success: function (res) {
            if (res.status) {
                window.location.reload();
            } else {
                alert(res.message || 'Unable to stop this timer.');
            }
        },
        error: function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to stop this timer.';
            alert(msg);
        }
    });
});

// =====================================================
// Live elapsed-time readout on the "My Timer" widget — a client-
// side ticker only, the true figure is always computed server-side
// from started_at/ended_at when the timer is stopped.
// =====================================================
function tickMyTimerElapsed() {
    var $box = $('#myTimerRunning');
    if (!$box.length) {
        return;
    }

    var started = new Date($box.data('started'));
    var minutes = Math.max(0, Math.floor((Date.now() - started.getTime()) / 60000));

    $('#myTimerElapsed').text(Math.floor(minutes / 60) + 'h ' + String(minutes % 60).padStart(2, '0') + 'm');
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableProjectTimeEntries.init();

    // Search
    $('#timeEntrySearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Filters
    $('#projectFilter, #employeeFilter, #billableFilter, #runningFilter').on('change', function () {
        dataTableInstance.draw();
    });

    // Conditional fields inside the remote modal
    $(document).on('change', '.te-project-select', function () {
        filterTimeEntryTaskOptions($(this).closest('form'), '.te-project-select', '.te-task-select');
    });
    $(document).on('change', '.ste-project-select', function () {
        filterTimeEntryTaskOptions($(this).closest('form'), '.ste-project-select', '.ste-task-select');
    });

    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (modalContent) {
        new MutationObserver(function () {
            filterTimeEntryTaskOptions('#modal_remote .modal-content', '.te-project-select', '.te-task-select');
            filterTimeEntryTaskOptions('#modal_remote .modal-content', '.ste-project-select', '.ste-task-select');
            bindTimeEntryDurationToggle('#modal_remote .modal-content');
        }).observe(modalContent, { childList: true, subtree: true });
    }

    // My Timer live ticker
    tickMyTimerElapsed();
    setInterval(tickMyTimerElapsed, 30000);

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
