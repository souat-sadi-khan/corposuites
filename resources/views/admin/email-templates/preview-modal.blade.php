<div class="modal-header fm-modal-head">
    <div>
        <h5 class="modal-title">Preview: {{ $template->name }}</h5>
        <p>Rendered with dummy data</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body fm-modal-body fm-body" style="max-height: 70vh; overflow-y: auto;">
    <div class="mb-3">
        <h6>Subject</h6>
        <div class="p-2 bg-light rounded">{{ $preview['subject'] }}</div>
    </div>

    <div class="mb-3">
        <h6>Body (HTML rendered)</h6>
        <div class="p-3 border rounded bg-white" style="min-height: 100px;">
            {!! $preview['body'] !!}
        </div>
    </div>

    <div class="mb-3">
        <h6>Dummy Data Used</h6>
        <pre class="p-2 bg-light rounded" style="font-size: 13px;">{{ json_encode($preview['data'], JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>

<div class="modal-footer fm-modal-foot">
    <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
        <i class="ri-close-large-line me-1"></i> Close
    </button>
</div>
