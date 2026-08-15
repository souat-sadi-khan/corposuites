<form class="ajax-form finance-project-budget-form" method="POST" action="{{ route('admin.finance-project-budgets.update', $financeProjectBudget->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Project Budget</h5>
            <p>Update: {{ $financeProjectBudget->budget_code }} ({{ $financeProjectBudget->version_label }}) &mdash; current total {{ number_format($financeProjectBudget->total_amount, 2) }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Project</label>
                <input type="text" class="form-control" value="{{ $financeProjectBudget->project?->name }} ({{ $financeProjectBudget->project?->project_code }})" disabled>
                {{-- Not editable: the version number is issued per project + period.
                     Submitted as a hidden field to satisfy validation; the service ignores it. --}}
                <input type="hidden" name="project_id" value="{{ $financeProjectBudget->project_id }}">
            </div>
            <div class="fm-field">
                <label>Title</label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $financeProjectBudget->title) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Period</label>
                <input type="text" class="form-control" value="{{ $financeProjectBudget->period_label }} ({{ $financeProjectBudget->period_type_label }})" disabled>
                {{-- Not editable: the version number is issued per project + period. --}}
                <input type="hidden" name="period_type" value="{{ $financeProjectBudget->period_type }}">
                <input type="hidden" name="period_start" value="{{ $financeProjectBudget->period_start->toDateString() }}">
                <input type="hidden" name="period_end" value="{{ $financeProjectBudget->period_end->toDateString() }}">
            </div>
            <div class="fm-field">
                <label>Budget State <span class="req">*</span></label>
                <select name="budget_status" class="form-select budget-status-select" required>
                    @foreach (['draft' => 'Draft', 'approved' => 'Approved', 'revised' => 'Revised', 'closed' => 'Closed'] as $value => $label)
                        <option value="{{ $value }}" {{ $financeProjectBudget->budget_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field budget-approval-field" style="display:none;">
                <label>Approved By</label>
                <select name="approved_by" class="form-select select">
                    <option value="">Not recorded</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}" {{ $financeProjectBudget->approved_by == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field budget-approval-field" style="display:none;">
                <label>Approved Date</label>
                <input type="date" class="form-control" name="approved_date" value="{{ old('approved_date', optional($financeProjectBudget->approved_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $financeProjectBudget->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $financeProjectBudget->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $financeProjectBudget->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Budget Lines <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm finance-project-budget-item-add">
                <i class="ri-add-line"></i> Add Line
            </button>
        </div>
        @php
            $existingFinanceProjectBudgetItems = $financeProjectBudget->items->map(function ($item) {
                return [
                    'chart_of_account_id' => $item->chart_of_account_id,
                    'planned_amount' => $item->planned_amount,
                    'notes' => $item->notes,
                ];
            });
        @endphp
        <div class="finance-project-budget-item-rows" data-existing='@json($existingFinanceProjectBudgetItems)'></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <small class="text-muted">Total Budget</small>
                <div class="fw-bold fpbud-total-preview">0.00</div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The budget code, version, project and period are fixed. The total is recalculated from the lines on save; moving the state off Approved clears the approval details.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>

    {{-- Account options source, consumed client-side by finance-project-budgets.js. No per-row AJAX. --}}
    <select class="d-none finance-project-budget-account-options">
        @foreach ($chartOfAccounts as $account)
            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
        @endforeach
    </select>
</form>
