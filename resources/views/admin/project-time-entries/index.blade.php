@extends('admin.layout.app', ['title' => 'Time Tracking', 'modal' => 'lg'])

@section('content')
    <!-- My Timer -->
    <div class="nx-card tl-card mb-3 p-3">
        @if (! $myEmployeeId)
            <div class="d-flex align-items-center gap-2 text-muted">
                <i class="ri-information-line"></i>
                Your admin account isn't linked to an employee record, so there's no personal timer here. Use "Add Time Entry" below to log time on someone's behalf.
            </div>
        @elseif ($runningEntry)
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3" id="myTimerRunning" data-started="{{ $runningEntry->started_at->toIso8601String() }}">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary"><i class="ri-timer-line"></i> Timer running</span>
                    <div>
                        <b>{{ $runningEntry->project->name }}</b>
                        @if ($runningEntry->task)
                            <span class="text-muted">&middot; {{ $runningEntry->task->title }}</span>
                        @endif
                        <br>
                        <small class="text-muted">Started {{ $runningEntry->started_at->format('H:i') }} &middot; <span id="myTimerElapsed">0h 00m</span> so far</small>
                    </div>
                </div>
                <button class="btn-nx-outline text-danger time-entry-stop-btn" data-url="{{ route('admin.project-time-entries.stop-timer', $runningEntry->id) }}">
                    <i class="ri-stop-circle-line"></i> Stop Timer
                </button>
            </div>
        @else
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="text-muted">
                    <i class="ri-timer-line"></i> No timer running right now.
                </div>
                <button id="openModal" data-url="{{ route('admin.project-time-entries.start-timer.form') }}" class="btn-nx-primary">
                    <i class="ri-play-circle-line"></i> Start Timer
                </button>
            </div>
        @endif
    </div>

    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="timeEntrySearch" placeholder="Search Time Entries">
        </div>

        <select id="projectFilter" class="form-select form-select-sm w-auto">
            <option value="">All Projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <select id="employeeFilter" class="form-select form-select-sm w-auto">
            <option value="">All Employees</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
            @endforeach
        </select>

        <select id="billableFilter" class="form-select form-select-sm w-auto">
            <option value="">Billable & Non-billable</option>
            <option value="1">Billable Only</option>
            <option value="0">Non-billable Only</option>
        </select>

        <select id="runningFilter" class="form-select form-select-sm w-auto">
            <option value="">All Entries</option>
            <option value="1">Running Only</option>
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
        <button id="openModal" data-url="{{ route('admin.project-time-entries.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Time Entry
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="timeEntryTable" data-url="{{ route('admin.project-time-entries.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Project / Task</th>
                        <th>Date</th>
                        <th>Duration</th>
                        <th>Billable</th>
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
    <script src="{{ asset('assets/system/js/pages/project-time-entries.js') }}"></script>
@endpush
