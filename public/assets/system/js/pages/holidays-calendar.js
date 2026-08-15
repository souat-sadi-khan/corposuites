document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('holidaysCalendar');
    if (!el || typeof FullCalendar === 'undefined') {
        return;
    }

    var calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek'
        },
        events: {
            url: el.dataset.url,
            method: 'GET'
        },
        eventDidMount: function (info) {
            if (info.event.extendedProps.description) {
                info.el.setAttribute('title', info.event.extendedProps.description);
            }
        }
    });

    calendar.render();
});
