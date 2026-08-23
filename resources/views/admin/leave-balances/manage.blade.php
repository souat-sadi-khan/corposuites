@php
    $isEdit = (bool) $employee;
@endphp
<form class="ajax-form leave-balance-form" method="POST"
      action="{{ $isEdit ? route('admin.leave-balances.manage.update', [$employee->id, $year]) : route('admin.leave-balances.store') }}">
    @if($isEdit) @method('PUT') @endif

    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">{{ $isEdit ? 'Manage Leave Balances' : 'Add Leave Balance Record' }}</h5>
            <p>
                @if($isEdit)
                    {{ $employee->full_name }} ({{ $employee->employee_code }}) &middot; {{ $year }}
                @else
                    One employee, one year — add every leave type this employee has a balance for
                @endif
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            @if($isEdit)
                <div class="fm-field">
                    <label>Employee</label>
                    <input type="text" class="form-control" value="{{ $employee->full_name }} ({{ $employee->employee_code }})" disabled>
                    {{-- Not editable here: moving a saved record to a different
                         employee/year would silently orphan it from what it
                         actually represents. Submitted as hidden fields to
                         satisfy validation; the controller/service never read
                         them from the request for an existing group anyway
                         (they come from the route params instead). --}}
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                </div>
                <div class="fm-field">
                    <label>Year</label>
                    <input type="text" class="form-control" value="{{ $year }}" disabled>
                    <input type="hidden" name="year" value="{{ $year }}">
                </div>
            @else
                <div class="fm-field">
                    <label>Employee <span class="req">*</span></label>
                    <select name="employee_id" class="form-select select" required data-placeholder="Select Employee">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option data-logo="{{ $emp->photo ? asset($emp->photo) : asset('assets/system/images/default-avatar.png') }}" data-desc="{{ $emp->email }}" value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="fm-field">
                    <label>Year <span class="req">*</span></label>
                    <input type="number" class="form-control" name="year" value="{{ $year }}" min="2000" max="2100" required>
                </div>
            @endif
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Leave Types <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm leave-balance-item-add">
                <i class="ri-add-line"></i> Add Leave Type
            </button>
        </div>
        <div class="leave-balance-item-rows"
             @if($isEdit) data-existing='@json($existingItems)' @endif></div>

        <p class="text-muted small mt-2 mb-0" id="leaveBalanceNoLinesHint" style="display:none;">
            <i class="ri-information-line"></i> Add at least one leave type above.
        </p>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Removing a leave type here and saving deletes its balance for this employee/year.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> {{ $isEdit ? 'Save Changes' : 'Create' }}
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>

    {{-- Leave type options source, consumed client-side by leave-balances.js. No per-row AJAX. --}}
    <select class="d-none leave-balance-type-options">
        @foreach($leaveTypes as $type)
            <option value="{{ $type->id }}" data-encashable="{{ $type->is_encashable ? '1' : '0' }}">{{ $type->name }}</option>
        @endforeach
    </select>
</form>

<script>
    // A route URL template with a placeholder id, filled in client-side per
    // row (the same "hand a route template to JS" technique already used
    // elsewhere in this project, e.g. Product Images' status route) — the
    // Encash action still targets a real, specific LeaveBalance row, same
    // endpoint as before this screen was restructured into a grouped view.
    window.leaveBalanceEncashUrlTemplate = @json(route('admin.leave-balances.encash', ['leaveBalance' => '__ID__']));
</script>
