<form class="ajax-form">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Connect an Attendance Device</h5>
            <p>Fingerprint, face, or ID-card reader — 3 steps</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">

        @if(!$token)
            <div class="alert alert-warning d-flex align-items-start gap-2">
                <i class="ri-alert-line fs-5"></i>
                <div>
                    No <strong>Attendance Device Token</strong> is set yet. Set one on this page first — the
                    example below won't work until you do.
                </div>
            </div>
        @endif

        <p>
            Any device (or the small software that comes with it) that can send a web request can log attendance
            automatically — no plugin or special integration is needed on our side.
        </p>

        <ol class="ps-3 mb-3">
            <li class="mb-2">
                <strong>Copy the token.</strong> This is a shared password. Your device sends it with every
                request so we know the request is really coming from your device, not a stranger.
            </li>
            <li class="mb-2">
                <strong>Point the device at this address:</strong>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <code class="flex-grow-1 text-break" id="deviceEndpointUrl">{{ $endpointUrl }}</code>
                    <button type="button" class="btn-nx-outline btn-sm copy-btn" data-copy-target="#deviceEndpointUrl" title="Copy">
                        <i class="ri-file-copy-line"></i>
                    </button>
                </div>
            </li>
            <li>
                <strong>Every punch sends a small message</strong> telling us who punched, and whether it was a
                check-in or check-out. The exact fields are below.
            </li>
        </ol>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Field</th>
                    <th class="text-center">Required</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>X-Attendance-Token</strong> (header)<br><small>The token from this page.</small></td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td><strong>employee_code</strong><br><small>Must match an Employee's code in the system.</small></td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td><strong>event</strong><br><small>Either <code>check_in</code> or <code>check_out</code>.</small></td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td><strong>occurred_at</strong><br><small>When the punch happened, e.g. <code>2026-08-20 09:05:00</code>.</small></td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td><strong>source</strong><br><small>One of <code>fingerprint</code>, <code>face</code>, <code>id_card</code>.</small></td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td><strong>latitude / longitude</strong><br><small>Only if the device itself has GPS. Most fixed devices don't need this.</small></td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p class="mb-2"><strong>Ready-to-test example</strong> — this exact command works with your current settings:</p>

        @php
            $curlExample = "curl -X POST '{$endpointUrl}' \\\n"
                . "  -H 'X-Attendance-Token: " . ($token ?: 'YOUR-TOKEN-HERE') . "' \\\n"
                . "  -H 'Content-Type: application/json' \\\n"
                . "  -d '{\"employee_code\":\"EMP-0001\",\"event\":\"check_in\",\"occurred_at\":\"" . now()->format('Y-m-d H:i:s') . "\",\"source\":\"fingerprint\"}'";
        @endphp

        <div class="position-relative">
            <pre class="p-3 rounded bg-dark text-light small mb-0" id="deviceCurlExample" style="white-space:pre-wrap;">{{ $curlExample }}</pre>
            <button type="button" class="btn-nx-outline btn-sm position-absolute top-0 end-0 m-2 copy-btn" data-copy-target="#deviceCurlExample" title="Copy">
                <i class="ri-file-copy-line"></i>
            </button>
        </div>

        <small class="text-muted d-block mt-2">
            Swap <code>EMP-0001</code> for a real employee code from your Employees list to try it for real.
        </small>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note"></span>
        <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
            <i class="ri-close-large-line me-1"></i> Close
        </button>
    </div>
</form>

<script>
    (function () {
        document.querySelectorAll('#modal_remote .copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = document.querySelector(btn.getAttribute('data-copy-target'));
                if (!target) return;

                navigator.clipboard.writeText(target.innerText).then(function () {
                    var icon = btn.querySelector('i');
                    var original = icon.className;
                    icon.className = 'ri-check-line text-success';
                    setTimeout(function () { icon.className = original; }, 1200);
                });
            });
        });
    })();
</script>
