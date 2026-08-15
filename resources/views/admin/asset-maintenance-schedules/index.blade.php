@extends('admin.layout.app', ['title' => 'Maintenance Schedule', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="scheduleSearch" placeholder="Search by title or asset">
        </div>

        <select id="dueFilter" class="form-select form-select-sm w-auto">
            <option value="">All Due States</option>
            <option value="overdue">Overdue</option>
            <option value="due_soon">Due in 30 Days</option>
        </select>

        <select id="scheduleStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Schedule States</option>
            <option value="active">Active</option>
            <option value="paused">Paused</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <select id="maintenanceTypeFilter" class="form-select form-select-sm w-auto">
            <option value="">All Types</option>
            <option value="preventive">Preventive</option>
            <option value="inspection">Inspection</option>
            <option value="calibration">Calibration</option>
            <option value="servicing">Servicing</option>
            <option value="other">Other</option>
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

        <button id="openModal" data-url="{{ route('admin.asset-maintenance-schedules.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Schedule Maintenance
        </button>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="scheduleTable" data-url="{{ route('admin.asset-maintenance-schedules.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Schedule</th>
                        <th>Type</th>
                        <th>Frequency</th>
                        <th>Next Due</th>
                        <th>Responsible</th>
                        <th>State</th>
                        <th>Status</th>
                        <th class="no-sort text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

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
    <script src="{{ asset('assets/system/js/pages/asset-maintenance-schedules.js') }}"></script>
@endpush
