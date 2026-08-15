<form class="ajax-form project-budget-form" method="POST" action="{{ route('admin.project-budgets.update', $projectBudget->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Project Budget</h5>
            <p>Update: {{ $projectBudget->budget_code }} ({{ $projectBudget->version_label }}) — current total {{ number_format($projectBudget->total_amount, 2) }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field">
                <label>Project</label>
                <input type="text" class="form-control" value="{{ $projectBudget->project?->name }} ({{ $projectBudget->project?->project_code }})" disabled>
                {{-- Not editable: the version number is issued per project. Submitted
                     as a hidden field to satisfy validation; the service ignores it. --}}
                <input type="hidden" name="project_id" value="{{ $projectBudget->project_id }}">

            </div>
            <div class="fm-field">
                <label>Title</label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $projectBudget->title) }}" autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Budget Date <span class="req">*</span></label>
                <input type="date" class="form-control" name="budget_date" value="{{ old('budget_date', optional($projectBudget->budget_date)->toDateString()) }}" required>
            </div>
            <div class="fm-field">
                <label>Budget State <span class="req">*</span></label>
                <select name="budget_status" class="form-select budget-status-select" required>
                    @foreach (['draft' => 'Draft', 'approved' => 'Approved', 'revised' => 'Revised', 'closed' => 'Closed'] as $value => $label)
                        <option value="{{ $value }}" {{ $projectBudget->budget_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field budget-approval-field" style="display:none;">
                <label>Approved By</label>
                <select name="approved_by" class="form-select select">
                    <option value="">Not recorded</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}" {{ $projectBudget->approved_by == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field budget-approval-field" style="display:none;">
                <label>Approved Date</label>
                <input type="date" class="form-control" name="approved_date" value="{{ old('approved_date', optional($projectBudget->approved_date)->toDateString()) }}">
            </div>
            <div class="fm-field">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $projectBudget->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $projectBudget->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $projectBudget->notes) }}</textarea>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">Budget Lines <span class="req">*</span></label>
            <button type="button" class="btn-nx-outline btn-sm project-budget-item-add">
                <i class="ri-add-line"></i> Add Line
            </button>
        </div>
        @php
            $existingBudgetItems = $projectBudget->items->map(function ($item) {
                return [
                    'category' => $item->category,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'notes' => $item->notes,
                ];
            });
        @endphp
        <div class="project-budget-item-rows" data-existing='@json($existingBudgetItems)'></div>

        <div class="d-flex justify-content-end mt-2">
            <div class="text-end">
                <small class="text-muted">Total Budget</small>
                <div class="fw-bold pbg-total-preview">0.00</div>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> The budget code and version are fixed. The total is recalculated from the lines on save; moving the state off Approved clears the approval details.
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

    {{-- Category options source, consumed client-side by project-budgets.js. No per-row AJAX. --}}
    <select class="d-none project-budget-category-options">
        @foreach (\App\Models\ProjectBudgetItem::CATEGORIES as $category)
            <option value="{{ $category }}">{{ ucfirst($category) }}</option>
        @endforeach
    </select>
</form>
