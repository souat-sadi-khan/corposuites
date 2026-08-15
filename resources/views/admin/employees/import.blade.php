<form class="ajax-form" method="POST" action="{{ route('admin.employees.import') }}" enctype="multipart/form-data">
    @csrf

    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Import Employees</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <p class="text-muted">
            Upload a CSV file with a header row. Expected columns:
            <code>Employee Code, First Name, Last Name, Email, Phone, Gender, Date of Birth, Date of Joining, Department, Designation</code>.
            Department/Designation values must match existing names exactly (left blank otherwise). Rows with a duplicate employee code or email are skipped.
        </p>
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>CSV File <span class="req">*</span></label>
                <input type="file" class="form-control" name="file" accept=".csv,.txt" required>
            </div>
        </div>
    </div>

    <div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">
        <button type="button" class="btn-nx-outline" data-bs-dismiss="offcanvas">
            <i class="ri-close-large-line me-1"></i> Cancel
        </button>

        <button id="submit" type="submit" class="btn-nx-primary">
            <i class="ri-upload-2-line me-1"></i> Import
        </button>
        <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>
