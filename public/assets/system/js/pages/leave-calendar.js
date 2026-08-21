document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('leaveCalendar');
    if (!el || typeof FullCalendar === 'undefined') {
        return;
    }

    var calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek,listWeek'
        },
        events: {
            url: el.dataset.url,
            method: 'GET'
        },
        eventDidMount: function (info) {
            var props = info.event.extendedProps || {};
            var tip = info.event.title;
            if (props.duration) {
                tip += ' (' + props.duration + ')';
            }
            if (props.status) {
                tip += ' - ' + props.status;
            }
            info.el.setAttribute('title', tip);
        }
    });

    calendar.render();
});
