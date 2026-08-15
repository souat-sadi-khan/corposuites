@extends('admin.layout.app', ['title' => 'Languages', 'offcanvas' => '70%', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="languageSearch" placeholder="Search languages">
        </div>

        <label class="tl-selected-chip" id="tlSelectedChip">
            <input type="checkbox" checked disabled>
            <span id="tlSelectedCount">0 Selected</span>
        </label>

        <!-- Bulk Actions displayed when rows are checked -->
        <button id="bulkDeleteBtn" data-url="{{ route('admin.languages.bulk-delete') }}" class="btn btn-sm btn-outline-danger py-1 px-2" style="display: none;">
            <i class="ri-delete-bin-line"></i> Delete Selected
        </button>

        <div class="tl-spacer"></div>

        <button data-width="70%" class="btn-nx-primary" id="openModal" data-url="{{ route('admin.languages.create') }}">
            <i class="ri-add-fill me-2"></i>
            Add Language
        </button>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table
                id="dataTable"
                data-url="{{ route('admin.languages.index') }}"
                class="tl-table"
                style="width:100%"
            >
                <thead>
                    <tr>
                        <th class="no-sort tl-check-col">
                            <input type="checkbox" id="selectAllChk">
                        </th>
                        <th>ID</th>
                        <th>Language</th>
                        <th>Code</th>
                        <th>Direction</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th>Updated</th>
                        <th class="no-sort text-end">
                            <i class="ri-more-2-line"></i>
                        </th>
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
    <script src="{{ asset('assets/system/js/pages/languages.js') }}"></script>
@endpush
