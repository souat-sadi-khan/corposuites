document.addEventListener('DOMContentLoaded', function () {
    const notifBtn = document.getElementById('notifBtn');
    const notifDd = document.getElementById('notifDd');
    const notifDot = document.getElementById('notifDot');
    const notifContainer = document.getElementById('notifContainer');

    notifBtn.addEventListener('click', function() {
        notifDd.style.display = notifDd.style.display === 'none' ? 'block' : 'none';
    });

    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }

    fetch('/admin/api/notifications')
        .then(res => res.json())
        .then(data => {
            updateHeaderList(data.notifications);
            if(data.unread_count > 0) notifDot.style.display = 'block';
        });

    function checkNewNotifications() {
        fetch('/admin/api/notifications/stream')
            .then(res => res.json())
            .then(notifications => {
                if (notifications.length > 0) {
                    notifDot.style.display = 'block';

                    const systemLogo = document.querySelector('meta[name="system-logo"]')?.getAttribute('content') || '/assets/system/images/logo.png';

                    notifications.forEach(data => {
                        if (Notification.permission === "granted") {
                            const pushNotif = new Notification(data.title, {
                                body: data.message,
                                icon: systemLogo
                            });

                            pushNotif.onclick = function() {
                                window.focus();
                                markNotificationAsRead(data.id);
                            };
                        }

                        const newHtml = `
                            <button class="dd-item" onclick="markNotificationAsRead(${data.id}, this)">
                                <span class="nd-dot"></span>
                                <span class="nd-text"><strong>${data.title}</strong><small>Just now · ${data.message}</small></span>
                            </button>
                        `;
                        notifContainer.insertAdjacentHTML('afterbegin', newHtml);
                    });
                }
            })
            .catch(err => console.error("Error fetching notifications:", err));
    }

    setInterval(checkNewNotifications, 10000);

    function updateHeaderList(notifications) {
        if(notifications.length === 0) {
            notifContainer.innerHTML = '<div class="p-3 text-center text-muted">No notifications</div>';
            return;
        }

        let html = '';
        notifications.forEach(n => {
            const isReadClass = n.is_read ? 'read' : '';
            html += `
                <button class="dd-item" onclick="markNotificationAsRead(${n.id}, this)">
                    <span class="nd-dot ${isReadClass}"></span>
                    <span class="nd-text"><strong>${n.title}</strong><small>${n.message}</small></span>
                </button>
            `;
        });
        notifContainer.innerHTML = html;
    }
});

function markNotificationAsRead(id, element = null) {
    fetch(`/admin/api/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(res => res.json())
    .then(data => {
        if(data.success && element) {
            const dot = element.querySelector('.nd-dot');
            if(dot) dot.classList.add('read');
        }
    });
}
