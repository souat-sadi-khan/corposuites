@extends('admin.layout.app', ['title' => 'Ticket Assignment', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="ticketAssignmentSearch" placeholder="Search Ticket Assignments">
        </div>

        <select id="agentFilter" class="form-select form-select-sm w-auto">
            <option value="">All Agents</option>
            @foreach ($admins as $admin)
                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
            @endforeach
        </select>

        <select id="assignmentStatusFilter" class="form-select form-select-sm w-auto">
            <option value="">All Assignment States</option>
            <option value="assigned">Assigned</option>
            <option value="reassigned">Reassigned</option>
            <option value="cancelled">Cancelled</option>
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
        <button id="openModal" data-url="{{ route('admin.ticket-assignments.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Assign Ticket
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="ticketAssignmentTable" data-url="{{ route('admin.ticket-assignments.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ticket</th>
                        <th>Agent</th>
                        <th>Assigned</th>
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
    <script src="{{ asset('assets/system/js/pages/ticket-assignments.js') }}"></script>
@endpush
