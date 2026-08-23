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
 *
 * The actual Check In / Check Out action now always goes through the shared
 * #awPunchModal (markup lives in header.blade.php, rendered once, globally)
 * instead of a native window.prompt() — it shows the employee's real
 * current location (an embedded OpenStreetMap iframe, no API key / no new
 * mapping library dependency) and an optional note before the punch is
 * actually sent. window.awOpenPunchModal() is exposed on `window` so BOTH
 * the header widget's own buttons AND the dedicated "My Attendance" page's
 * buttons (see attendance-portal/index.blade.php) can trigger the exact
 * same modal/flow — one implementation, two entry points, so they can never
 * drift out of sync with each other.
 */
$(function () {
    if (!$('#awPunchModal').length) {
        return; // no employee-linked widget on this page at all
    }

    var awBusy = false;
    var awPunchModalEl = document.getElementById('awPunchModal');
    var awPunchModal = new bootstrap.Modal(awPunchModalEl);
    var awPunchState = { url: null, latitude: null, longitude: null, ready: false };

    function awSetMessage(text, kind) {
        var $msg = $('#awMessage');
        if (!$msg.length) return;
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

    function awPunchResetModal(actionLabel) {
        awPunchState = { url: null, latitude: null, longitude: null, ready: false };
        $('#awPunchModalTitle').html(
            (actionLabel && actionLabel.toLowerCase().indexOf('out') !== -1 ? '<i class="ri-logout-circle-fill"></i> Check Out' : '<i class="ri-login-circle-fill"></i> Check In')
        );
        $('#awPunchConfirmLabel').text('Confirm');
        $('#awPunchLoading').removeClass('d-none');
        $('#awPunchLocationContent').addClass('d-none');
        $('#awPunchLocationError').addClass('d-none');
        $('#awPunchNotes').val('');
        $('#awPunchMessage').removeClass('is-error is-success').text('');
        $('#awPunchConfirmBtn').prop('disabled', true);
    }

    function awPunchShowLocation(position) {
        var lat = position.coords.latitude;
        var lng = position.coords.longitude;
        awPunchState.latitude = lat;
        awPunchState.longitude = lng;
        awPunchState.ready = true;

        var d = 0.003; // a small bbox around the point for the embed frame
        var bbox = (lng - d) + ',' + (lat - d) + ',' + (lng + d) + ',' + (lat + d);
        $('#awPunchMapFrame').attr('src', 'https://www.openstreetmap.org/export/embed.html?bbox=' + bbox + '&layer=mapnik&marker=' + lat + ',' + lng);
        $('#awPunchMapLink').attr('href', 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=17/' + lat + '/' + lng);
        $('#awPunchCoordsText').text(lat.toFixed(6) + ', ' + lng.toFixed(6));

        $('#awPunchLoading').addClass('d-none');
        $('#awPunchLocationError').addClass('d-none');
        $('#awPunchLocationContent').removeClass('d-none');
        $('#awPunchConfirmBtn').prop('disabled', false);
    }

    function awPunchShowLocationError(message) {
        $('#awPunchLoading').addClass('d-none');
        $('#awPunchLocationContent').addClass('d-none');
        $('#awPunchLocationErrorText').text(message);
        $('#awPunchLocationError').removeClass('d-none');
        $('#awPunchConfirmBtn').prop('disabled', true);
    }

    /**
     * Opens the shared punch modal and immediately starts resolving the
     * device's current location — exposed on window so both this file's
     * own Check In/Out buttons AND the "My Attendance" page's buttons can
     * call it. `url` is the check-in/check-out endpoint to post to.
     */
    window.awOpenPunchModal = function (url, actionLabel) {
        if (awBusy) return;

        awPunchResetModal(actionLabel);
        awPunchState.url = url;
        awPunchModal.show();

        if (!navigator.geolocation) {
            awPunchShowLocationError('Location services are unavailable on this device.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            awPunchShowLocation,
            function () { awPunchShowLocationError('Please allow location access to continue, then try again.'); },
            { enableHighAccuracy: true, timeout: 15000 }
        );
    };

    $('#awPunchConfirmBtn').on('click', function () {
        if (awBusy || !awPunchState.ready || !awPunchState.url) return;

        awBusy = true;
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#awPunchMessage').removeClass('is-error is-success').text('Working …');

        $.post(awPunchState.url, {
            latitude: awPunchState.latitude,
            longitude: awPunchState.longitude,
            source: 'browser_geolocation',
            notes: $('#awPunchNotes').val()
        }).done(function (response) {
            if (response.status) {
                awPunchModal.hide();
                awRefresh().done(function () {
                    awSetMessage(response.message, 'success');
                });
                // My Attendance page (if this modal was opened from there)
                // reloads to pick up the fresh table/report — see its own
                // trigger wiring below.
                if (typeof window.awOnPunchSuccess === 'function') {
                    window.awOnPunchSuccess(response);
                }
            } else {
                $('#awPunchMessage').removeClass('is-success').addClass('is-error').text(response.message);
                $btn.prop('disabled', false);
            }
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to record attendance.';
            $('#awPunchMessage').removeClass('is-success').addClass('is-error').text(message);
            $btn.prop('disabled', false);
        }).always(function () {
            awBusy = false;
        });
    });

    // Delegated from #attendanceWidgetDd (a stable container that never gets
    // replaced — only its #awBody child does) rather than document: theme.js
    // registers `$('.tb-dd').on('click', e => e.stopPropagation())` on every
    // dropdown (including this one) so an in-dropdown click doesn't bubble
    // up and trigger the global click-anywhere-closes-all-dropdowns handler.
    // That same stopPropagation would silently swallow a document-level
    // delegated handler before it ever saw the click.
    $('#attendanceWidgetDd').on('click', '#awCheckInBtn', function () {
        window.awOpenPunchModal(window.attendanceWidgetRoutes.checkIn, 'Check In');
    });

    $('#attendanceWidgetDd').on('click', '#awCheckOutBtn', function () {
        window.awOpenPunchModal(window.attendanceWidgetRoutes.checkOut, 'Check Out');
    });
});
