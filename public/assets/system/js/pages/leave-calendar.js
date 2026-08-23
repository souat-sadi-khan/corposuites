document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('leaveCalendar');
    if (!el || typeof FullCalendar === 'undefined') {
        return;
    }

    // Reuses main.js's own generic remote-modal loader (the same one every
    // other "View Details"/Add/Edit button on this project already uses)
    // rather than re-implementing a second modal-fetch flow here — a click
    // on a calendar event just sets the hidden #openModal trigger's own
    // data-url and clicks it.
    if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
        _componentRemoteModalLoadAfterAjax();
    }

    function currentFilters() {
        return {
            employee_id: $('#lcEmployee').val() || '',
            leave_type_id: $('#lcLeaveType').val() || '',
            department_id: $('#lcDepartment').val() || '',
            show_rejected: $('#lcShowRejected').is(':checked') ? 1 : 0
        };
    }

    function updateStats(events) {
        var pending = 0, approved = 0;
        var people = {};

        events.forEach(function (ev) {
            var status = (ev.extendedProps || {}).status;
            if (status === 'pending') pending++;
            if (status === 'approved') approved++;
            var name = (ev.extendedProps || {}).employee;
            if (name) people[name] = true;
        });

        $('#lcStatTotal').text(events.length);
        $('#lcStatPending').text(pending);
        $('#lcStatApproved').text(approved);
        $('#lcStatPeople').text(Object.keys(people).length);
    }

    var calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek,listMonth'
        },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'List' },
        dayMaxEvents: 3,
        events: function (fetchInfo, successCallback, failureCallback) {
            var params = Object.assign({ start: fetchInfo.startStr, end: fetchInfo.endStr }, currentFilters());
            $.get(el.dataset.url, params)
                .done(function (events) {
                    updateStats(events);
                    successCallback(events);
                })
                .fail(function () { failureCallback(); });
        },
        eventDidMount: function (info) {
            var props = info.event.extendedProps || {};
            var tip = info.event.title;
            if (props.duration) tip += ' (' + props.duration + ')';
            if (props.status) tip += ' — ' + props.status.charAt(0).toUpperCase() + props.status.slice(1);
            if (props.department) tip += ' · ' + props.department;
            info.el.setAttribute('title', tip);
        },
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            var url = (info.event.extendedProps || {}).detailsUrl;
            if (!url) return;
            $('#openModal').attr('data-url', url).trigger('click');
        }
    });

    calendar.render();

    // Filters — every change simply re-asks the calendar to re-fetch events
    // for whatever range is currently on screen, through the SAME events()
    // function above, so filtering never re-derives a second data path.
    $('#lcEmployee, #lcLeaveType, #lcDepartment').on('change', function () {
        calendar.refetchEvents();
    });
    $('#lcShowRejected').on('change', function () {
        calendar.refetchEvents();
    });
});
