/**
 * Header attendance widget — check-in/check-out actions and its own
 * refresh-after-action call. No-ops entirely when the widget isn't rendered
 * (a plain, non-employee-linked admin account), since #attendanceWidgetBtn
 * simply won't exist on the page for them.
 *
 * Business logic (late/half-day/geofence/etc.) lives entirely server-side
 * in AttendancePortalController — this file only drives the UI and posts to
 * the SAME endpoints the dedicated "My Attendance" page already uses, so
 * there is exactly one place that decides whether a punch is valid.
 */
$(function () {
    if (!$('#attendanceWidgetBtn').length) {
        return;
    }

    var awBusy = false;

    function awSetMessage(text, kind) {
        var $msg = $('#awMessage');
        $msg.removeClass('is-error is-success').text(text || '');
        if (kind) $msg.addClass('is-' + kind);
    }

    // Returns the underlying jqXHR so a caller can chain .done() to run
    // something AFTER the fresh #awBody markup (including a brand new,
    // empty #awMessage element) has replaced the old one — setting a
    // message BEFORE this resolves would just get wiped out instantly.
    function awRefresh() {
        return $.get(window.attendanceWidgetRoutes.status)
            .done(function (html) {
                $('#awBody').html(html);
                var $content = $('#awBody .aw-content');
                var state = $content.data('state');
                var label = $content.data('label');
                var time = $content.data('time');

                $('#attendanceWidgetBtn')
                    .attr('class', 'aw-chip aw-chip-' + state);
                $('#awChipLabel').text(label);
                $('#awChipTime').text(time ? '· ' + time : '');
            });
    }

    function awPunch(url, actionLabel) {
        if (awBusy) return;

        if (!navigator.geolocation) {
            awSetMessage('Location services are unavailable on this device.', 'error');
            return;
        }

        awBusy = true;
        awSetMessage(actionLabel + ' …'); // "Checking in ..." / "Checking out ..."
        $('#awBody .aw-action button').prop('disabled', true);

        navigator.geolocation.getCurrentPosition(
            function (position) {
                $.post(url, {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    source: 'browser_geolocation'
                }).done(function (response) {
                    if (response.status) {
                        // Refresh FIRST (it replaces #awMessage along with the
                        // rest of #awBody), then show the success message in
                        // the newly-rendered element — otherwise it would be
                        // set and instantly wiped out in the same tick.
                        awRefresh().done(function () {
                            awSetMessage(response.message, 'success');
                        });
                    } else {
                        awSetMessage(response.message, 'error');
                        $('#awBody .aw-action button').prop('disabled', false);
                    }
                }).fail(function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to record attendance.';
                    awSetMessage(message, 'error');
                    $('#awBody .aw-action button').prop('disabled', false);
                }).always(function () {
                    awBusy = false;
                });
            },
            function () {
                awSetMessage('Please allow location access to continue.', 'error');
                $('#awBody .aw-action button').prop('disabled', false);
                awBusy = false;
            },
            { enableHighAccuracy: true, timeout: 15000 }
        );
    }

    // Delegated from #attendanceWidgetDd (a stable container that never gets
    // replaced — only its #awBody child does) rather than document: theme.js
    // registers `$('.tb-dd').on('click', e => e.stopPropagation())` on every
    // dropdown (including this one) so an in-dropdown click doesn't bubble up
    // and trigger the global click-anywhere-closes-all-dropdowns handler.
    // That same stopPropagation would silently swallow a document-level
    // delegated handler before it ever saw the click.
    $('#attendanceWidgetDd').on('click', '#awCheckInBtn', function () {
        awPunch(window.attendanceWidgetRoutes.checkIn, 'Checking in');
    });

    $('#attendanceWidgetDd').on('click', '#awCheckOutBtn', function () {
        awPunch(window.attendanceWidgetRoutes.checkOut, 'Checking out');
    });
});
