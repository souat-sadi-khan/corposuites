@extends('admin.layout.app', ['title' => 'Workflow Notification Triggers', 'modal' => 'lg'])

@section('content')
    @if($workflowDefinitionId)
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="ri-filter-3-line me-1"></i> Showing notification triggers for workflow definition: <strong>{{ $workflowDefinition->name ?? $workflowDefinitionId }}</strong>.</span>
            <a href="{{ route('admin.workflow-notification-triggers.index') }}" class="btn-nx-outline btn-sm">Clear Filter</a>
        </div>
    @endif

    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="workflowNotificationTriggerSearch" placeholder="Search Notification Triggers">
        </div>

        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="tlFilterBtn" title="Filter">
                <i class="ri-equalizer-line"></i>
            </button>

            <div class="tl-filter-dd" id="tlFilterDd">
                <div class="tl-filter-dd-title">
                    Filter by Status
                </div>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="1" checked>
                    Active
                </label>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="0" checked>
                    Inactive
                </label>
            </div>
        </div>

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.workflow-notification-triggers.create', $workflowDefinitionId ? ['workflow_definition_id' => $workflowDefinitionId] : []) }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Notification Trigger
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="workflowNotificationTriggerTable" data-url="{{ route('admin.workflow-notification-triggers.index') }}" data-workflow-definition-id="{{ $workflowDefinitionId }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event</th>
                        <th>Notify</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th class="no-sort text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="tl-footer">
            <div class="tl-info" id="tlInfo"></div>
            <div class="tl-pagination">
                <button class="tl-page-btn" id="tlPrev" title="Previous page">
                    <i class="ri-arrow-left-s-line"></i>
                </button>
                <button class="tl-page-btn" id="tlNext" title="Next page">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/workflow-notification-triggers.js') }}"></script>
@endpush
