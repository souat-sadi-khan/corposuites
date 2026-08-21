{{--
    Deliberately NOT an .ajax-form — a plain GET form that submits straight
    to the certificate print route in a new tab, the same pattern Barcode
    Generator's own index screen already established for "configure a
    few things, then open the printable result".
--}}
<form method="GET" action="{{ route('admin.employees.salary-certificate', $employee->id) }}" target="_blank">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Generate Salary Certificate</h5>
            <p>For: {{ $employee->full_name }} ({{ $employee->employee_code }})</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Purpose</label>
                <input type="text" class="form-control" name="purpose" placeholder="e.g., Bank Loan, Visa Application, Personal Record">
            </div>
            <div class="fm-field">
                <label>Issue Date</label>
                <input type="date" class="form-control" name="issue_date" value="{{ now()->toDateString() }}">
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The certificate is generated from this employee's current active Salary Structure. Leave Purpose blank for a generic "whatever purpose it may serve" wording.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" data-bs-dismiss="modal">
                <i class="ri-file-download-line me-1"></i> Generate
            </button>
        </div>
    </div>
</form>
