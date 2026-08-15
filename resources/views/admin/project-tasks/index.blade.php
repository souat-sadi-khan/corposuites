@extends('admin.layout.app', ['title' => 'Tasks', 'modal' => 'xl'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="taskSearch" placeholder="Search Tasks">
        </div>

        <select id="projectFilter" class="form-select form-select-sm w-auto">
            <option value="">All Projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <select id="taskStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Task States</option>
            @foreach (\App\Models\ProjectTask::STATUSES as $taskStatus)
                <option value="{{ $taskStatus }}">{{ ucwords(str_replace('_', ' ', $taskStatus)) }}</option>
            @endforeach
        </select>

        <select id="priorityFilter" class="form-select form-select-sm w-auto">
            <option value="">All Priorities</option>
            @foreach (\App\Models\ProjectTask::PRIORITIES as $priority)
                <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
            @endforeach
        </select>

        <select id="ownerFilter" class="form-select form-select-sm w-auto">
            <option value="">All Assignees</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
            @endforeach
        </select>

        <select id="overdueFilter" class="form-select form-select-sm w-auto">
            <option value="">All Due Dates</option>
            <option value="1">Overdue Only</option>
        </select>

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
        <button id="openModal" data-url="{{ route('admin.project-tasks.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Task
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="taskTable" data-url="{{ route('admin.project-tasks.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task</th>
                        <th>Project</th>
                        <th>Assignee</th>
                        <th>Schedule</th>
                        <th>Progress</th>
                        <th>Priority</th>
                        <th>State</th>
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
    <script src="{{ asset('assets/system/js/pages/project-tasks.js') }}"></script>
@endpush
