@php
    $statusBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
    $stepStatusMeta = [
        'approved' => ['label' => 'Approved', 'class' => 'success', 'icon' => 'ri-checkbox-circle-fill'],
        'rejected' => ['label' => 'Rejected', 'class' => 'danger', 'icon' => 'ri-close-circle-fill'],
        'pending' => ['label' => 'In Progress', 'class' => 'warning', 'icon' => 'ri-time-line'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'secondary', 'icon' => 'ri-forbid-line'],
        'not_reached' => ['label' => 'Not Reached', 'class' => 'muted', 'icon' => 'ri-more-line'],
    ];
@endphp

<div class="modal-header fm-modal-head">
    <div>
        <h5 class="modal-title"><i class="ri-file-list-3-line"></i> Leave Request Details</h5>
        <p>{{ $leaveRequest->employee->full_name ?? '—' }} · {{ $leaveRequest->leaveType->name ?? '—' }}</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body fm-modal-body fm-body lrd-body">
    <div class="lrd-summary">
        <div class="lrd-summary-row">
            <div class="lrd-summary-item">
                <span class="lrd-summary-label">Employee</span>
                <span class="lrd-summary-value">
                    {{ $leaveRequest->employee->full_name ?? '—' }}
                    <small class="text-muted d-block">{{ $leaveRequest->employee->employee_code ?? '' }} · {{ $leaveRequest->employee->department->name ?? '—' }} / {{ $leaveRequest->employee->designation->name ?? '—' }}</small>
                </span>
            </div>
            <div class="lrd-summary-item">
                <span class="lrd-summary-label">Leave Type</span>
                <span class="lrd-summary-value">{{ $leaveRequest->leaveType->name ?? '—' }}</span>
            </div>
            <div class="lrd-summary-item">
                <span class="lrd-summary-label">Status</span>
                <span class="lrd-summary-value">
                    <span class="badge bg-{{ $statusBadge[$leaveRequest->approval_status] ?? 'secondary' }}-subtle text-{{ $statusBadge[$leaveRequest->approval_status] ?? 'secondary' }}">
                        {{ ucfirst($leaveRequest->approval_status) }}
                    </span>
                </span>
            </div>
        </div>
        <div class="lrd-summary-row">
            <div class="lrd-summary-item">
                <span class="lrd-summary-label">Duration</span>
                <span class="lrd-summary-value">
                    {{ optional($leaveRequest->start_date)->format('d M Y') }}
                    @if($leaveRequest->start_date && $leaveRequest->end_date && !$leaveRequest->start_date->isSameDay($leaveRequest->end_date))
                        &rarr; {{ $leaveRequest->end_date->format('d M Y') }}
                    @endif
                    <small class="text-muted d-block">
                        {{ $leaveRequest->total_days }} day{{ $leaveRequest->total_days == 1 ? '' : 's' }}
                        @if($leaveRequest->duration_type === 'half_day')
                            · Half Day ({{ ucfirst(str_replace('_', ' ', $leaveRequest->half_day_session ?? '')) }})
                        @endif
                    </small>
                </span>
            </div>
            <div class="lrd-summary-item lrd-summary-wide">
                <span class="lrd-summary-label">Reason</span>
                <span class="lrd-summary-value">{{ $leaveRequest->reason ?: '—' }}</span>
            </div>
            @if($leaveRequest->attachment)
                <div class="lrd-summary-item">
                    <span class="lrd-summary-label">Attachment</span>
                    <span class="lrd-summary-value">
                        <a href="{{ asset($leaveRequest->attachment) }}" target="_blank" rel="noopener">
                            <i class="ri-attachment-2"></i> View Attachment
                        </a>
                    </span>
                </div>
            @endif
        </div>

        @if($leaveRequest->approval_status === 'rejected' || $leaveRequest->cancellation_reason)
            <div class="lrd-note lrd-note-{{ $leaveRequest->approval_status === 'rejected' ? 'danger' : 'secondary' }}">
                @if($leaveRequest->approval_status === 'rejected')
                    <i class="ri-close-circle-line"></i> This request was rejected.
                @endif
                @if($leaveRequest->cancellation_reason)
                    <div><strong>Cancellation reason:</strong> {{ $leaveRequest->cancellation_reason }}
                        @if($leaveRequest->cancelled_at) <small class="text-muted">({{ $leaveRequest->cancelled_at->format('d M Y, h:i A') }})</small>@endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <hr class="lrd-divider">

    <div class="lrd-workflow-head">
        <h6><i class="ri-git-branch-line"></i> Approval Workflow</h6>
        @if($instance)
            <span class="badge bg-{{ $statusBadge[$instance->current_status] ?? 'secondary' }}-subtle text-{{ $statusBadge[$instance->current_status] ?? 'secondary' }}">
                {{ ucfirst($instance->current_status) }}
            </span>
        @endif
    </div>

    @if(!$instance)
        <div class="lrd-no-workflow">
            <i class="ri-information-line"></i>
            No multi-step approval workflow is configured for Leave Requests — this request is approved or
            rejected directly by any authorized approver, without going through a step-by-step chain.
        </div>
    @else
        <div class="lrd-workflow-meta">
            <span><i class="ri-flow-chart"></i> {{ $instance->workflowDefinition->name ?? 'Workflow' }}</span>
            @if($instance->initiatedBy)
                <span><i class="ri-user-line"></i> Initiated by {{ $instance->initiatedBy->name }}</span>
            @endif
            @if($instance->completed_at)
                <span><i class="ri-time-line"></i> Completed {{ $instance->completed_at->format('d M Y, h:i A') }}</span>
            @endif
        </div>

        <div class="lrd-stepper">
            @forelse($steps as $i => $entry)
                @php
                    $step = $entry['step'];
                    $meta = $stepStatusMeta[$entry['status']] ?? $stepStatusMeta['not_reached'];
                @endphp
                <div class="lrd-step lrd-step-{{ $meta['class'] }}">
                    <div class="lrd-step-marker">
                        <div class="lrd-step-dot"><i class="{{ $meta['icon'] }}"></i></div>
                        @if(!$loop->last)<div class="lrd-step-line"></div>@endif
                    </div>
                    <div class="lrd-step-content">
                        <div class="lrd-step-head">
                            <span class="lrd-step-name">Step {{ $step->step_order }}: {{ $step->name }}</span>
                            <span class="badge bg-{{ $meta['class'] === 'muted' ? 'secondary' : $meta['class'] }}-subtle text-{{ $meta['class'] === 'muted' ? 'secondary' : $meta['class'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </div>
                        <div class="lrd-step-sub">
                            {{ ucwords(str_replace('_', ' ', $step->approval_type)) }} ·
                            @forelse($step->approvers as $approver)
                                <span class="lrd-approver-chip">{{ $approver->approver_label }}</span>@if(!$loop->last), @endif
                            @empty
                                <span class="text-muted">No approvers configured</span>
                            @endforelse
                        </div>

                        @if($entry['approvals']->isNotEmpty())
                            <div class="lrd-step-actions">
                                @foreach($entry['approvals'] as $approval)
                                    <div class="lrd-action-row">
                                        <span class="badge bg-{{ $statusBadge[$approval->action] ?? 'secondary' }}-subtle text-{{ $statusBadge[$approval->action] ?? 'secondary' }}">
                                            {{ ucfirst($approval->action) }}
                                        </span>
                                        <span class="lrd-action-by">{{ $approval->approver->name ?? 'Unknown' }}</span>
                                        <span class="lrd-action-time text-muted">{{ optional($approval->acted_at)->format('d M Y, h:i A') }}</span>
                                        @if($approval->remarks)
                                            <span class="lrd-action-remarks">"{{ $approval->remarks }}"</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-muted small">No workflow steps configured.</div>
            @endforelse
        </div>
    @endif
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-nx-outline btn-sm" data-bs-dismiss="modal">Close</button>
</div>

{{--
    A plain inline <style> tag, NOT @push('styles') — this partial is loaded
    via AJAX straight into #modal_remote's .modal-content (main.js's
    _componentRemoteModalLoadAfterAjax()), a separate Blade render pass from
    the outer page, so anything pushed into the 'styles' stack here would
    never reach any @stack('styles') echo and would be silently dropped.
    A <style> tag injected via .html() is executed by the browser exactly
    like any other markup, so this works correctly here.
--}}
<style>
            .lrd-body { max-height: 70vh; overflow-y: auto; }
            .lrd-summary-row { display: flex; flex-wrap: wrap; gap: 16px; padding: 10px 0; border-bottom: 1px dashed var(--border-lt); }
            .lrd-summary-row:last-of-type { border-bottom: none; }
            .lrd-summary-item { flex: 1 1 160px; min-width: 140px; }
            .lrd-summary-wide { flex: 2 1 240px; }
            .lrd-summary-label { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--tx-3); margin-bottom: 3px; }
            .lrd-summary-value { font-size: 13px; color: var(--tx-1); font-weight: 600; }
            .lrd-note { margin-top: 10px; padding: 9px 12px; border-radius: 9px; font-size: 12.5px; }
            .lrd-note-danger { background: var(--red-s); color: var(--red); }
            .lrd-note-secondary { background: var(--bg-base); color: var(--tx-2); }
            .lrd-divider { border-top: 1px solid var(--border-lt); margin: 14px 0; }
            .lrd-workflow-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
            .lrd-workflow-head h6 { display: flex; align-items: center; gap: 6px; margin: 0; font-size: 13.5px; font-weight: 700; }
            .lrd-no-workflow { display: flex; align-items: flex-start; gap: 8px; padding: 10px 12px; border-radius: 9px; background: var(--bg-base); border: 1px solid var(--border-lt); color: var(--tx-2); font-size: 12.5px; }
            .lrd-workflow-meta { display: flex; flex-wrap: wrap; gap: 14px; font-size: 11.5px; color: var(--tx-3); margin-bottom: 12px; }
            .lrd-workflow-meta i { color: var(--accent); }
            .lrd-stepper { display: flex; flex-direction: column; }
            .lrd-step { display: flex; gap: 12px; }
            .lrd-step-marker { display: flex; flex-direction: column; align-items: center; }
            .lrd-step-dot { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; background: var(--bg-base); border: 2px solid var(--border-lt); color: var(--tx-3); }
            .lrd-step-success .lrd-step-dot { background: var(--green-s); border-color: var(--green); color: var(--green); }
            .lrd-step-danger .lrd-step-dot { background: var(--red-s); border-color: var(--red); color: var(--red); }
            .lrd-step-warning .lrd-step-dot { background: rgba(245,158,11,.14); border-color: #f59e0b; color: #f59e0b; }
            .lrd-step-line { width: 2px; flex: 1; min-height: 24px; background: var(--border-lt); margin: 2px 0; }
            .lrd-step-content { flex: 1; padding-bottom: 18px; }
            .lrd-step-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
            .lrd-step-name { font-size: 13px; font-weight: 700; color: var(--tx-1); }
            .lrd-step-sub { font-size: 11.5px; color: var(--tx-3); margin-top: 3px; }
            .lrd-approver-chip { display: inline-block; background: var(--bg-base); border: 1px solid var(--border-lt); border-radius: 999px; padding: 1px 8px; font-weight: 600; color: var(--tx-2); }
            .lrd-step-actions { margin-top: 7px; display: flex; flex-direction: column; gap: 5px; }
            .lrd-action-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 11.5px; background: var(--bg-base); border-radius: 8px; padding: 5px 9px; }
            .lrd-action-by { font-weight: 700; color: var(--tx-1); }
            .lrd-action-remarks { color: var(--tx-3); font-style: italic; }
</style>
