@extends('admin.layout.app', ['title' => 'All Notifications', 'offcanvas' => '50%'])

@section('content')
<div class="tl-toolbar">
    <div class="tl-search">
        <i class="ri-search-line"></i>
        <input type="text" id="notifSearch" placeholder="Search notifications">
    </div>

    <div class="d-flex align-items-center gap-2">
        <label class="tl-selected-chip" id="tlSelectedChip" style="display: none;">
            <input type="checkbox" checked disabled>
            <span id="tlSelectedCount">0 Selected</span>
        </label>

        <!-- Bulk Actions displayed when rows are checked -->
        <button id="bulkDeleteBtn" class="btn btn-sm btn-outline-danger py-1 px-2" style="display: none;">
            <i class="ri-delete-bin-line"></i> Delete Selected
        </button>
    </div>

    <div class="tl-spacer"></div>
</div>

<div class="nx-card tl-card">
    <div class="table-responsive">
        <table
            id="dataTable"
            data-url="{{ route('admin.notifications') }}"
            class="tl-table"
            style="width:100%"
        >
            <thead>
                <tr>
                    <th class="no-sort tl-check-col">
                        <input type="checkbox" id="selectAllChk">
                    </th>
                    <th>ID</th>
                    <th>Notification Details</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th class="no-sort text-center">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="tl-footer">
        <div class="tl-info" id="tlInfo"></div>
        <div class="tl-pagination">
            <button class="tl-page-btn" id="tlPrev" title="Previous page">
                <i class="ri-arrow-left-line"></i>
            </button>
            <button class="tl-page-btn" id="tlNext" title="Next page">
                <i class="ri-arrow-right-line"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/notifications-page.js') }}"></script>
@endpush
