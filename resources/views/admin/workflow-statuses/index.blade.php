@extends('admin.layout.app', ['title' => 'Workflow Statuses', 'modal' => 'lg'])

@section('content')
    @if($workflowDefinitionId)
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="ri-filter-3-line me-1"></i> Showing statuses for workflow definition: <strong>{{ $workflowDefinition->name ?? $workflowDefinitionId }}</strong>.</span>
            <a href="{{ route('admin.workflow-statuses.index') }}" class="btn-nx-outline btn-sm">Clear Filter</a>
        </div>
    @endif

    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="workflowStatusSearch" placeholder="Search Workflow Statuses">
        </div>

        @unless($workflowDefinitionId)
            <div class="fm-field" style="min-width:260px;">
                <select id="workflowStatusDefinitionFilter" class="form-select select">
                    <option value="">All Workflow Definitions</option>
                    @foreach(\App\Models\WorkflowDefinition::orderBy('name')->get() as $definition)
                        <option value="{{ $definition->id }}">{{ $definition->name }}</option>
                    @endforeach
                </select>
            </div>
        @endunless

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.workflow-statuses.create', $workflowDefinitionId ? ['workflow_definition_id' => $workflowDefinitionId] : []) }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Workflow Status
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="workflowStatusTable" data-url="{{ route('admin.workflow-statuses.index') }}" data-workflow-definition-id="{{ $workflowDefinitionId }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Key</th>
                        <th>Label</th>
                        <th>Color</th>
                        <th>Terminal</th>
                        <th>Sort Order</th>
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
    <script src="{{ asset('assets/system/js/pages/workflow-statuses.js') }}"></script>
@endpush
