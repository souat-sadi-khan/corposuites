@extends('admin.layout.app', ['title' => 'Project Teams', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="teamMemberSearch" placeholder="Search Team Members">
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

        <select id="teamRoleFilter" class="form-select form-select-sm w-auto">
            <option value="">All Roles</option>
            @foreach (\App\Models\ProjectTeamMember::ROLES as $role)
                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
            @endforeach
        </select>

        <select id="membershipFilter" class="form-select form-select-sm w-auto">
            <option value="">All Memberships</option>
            <option value="current">Current Only</option>
            <option value="past">Past Only</option>
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
        <button id="openModal" data-url="{{ route('admin.project-team-members.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Team Member
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="teamMemberTable" data-url="{{ route('admin.project-team-members.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Project</th>
                        <th>Role</th>
                        <th>Allocation</th>
                        <th>Membership</th>
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
    <script src="{{ asset('assets/system/js/pages/project-team-members.js') }}"></script>
@endpush
