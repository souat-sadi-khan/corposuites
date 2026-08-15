@extends('admin.layout.app', ['title' => 'Roles & Permission', 'modal' => 'lg', 'offcanvas' => '85%'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="roleSearch" placeholder="Search roles">
        </div>

        <label class="tl-selected-chip" id="tlSelectedChip">
            <input type="checkbox" checked disabled>
            <span id="tlSelectedCount">0 Selected</span>
        </label>

        <div class="tl-spacer"></div>

        <button class="btn-nx-primary" id="openModal" data-url="{{ route('admin.roles.create') }}">
            <i class="ri-add-fill me-2"></i>
            Add Role
        </button>
    </div>

    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="dataTable" data-url="{{ route('admin.roles.index') }}" class="tl-table">
                <thead>
                    <tr>
                        <th class="no-sort tl-check-col">
                            <input type="checkbox" id="selectAllChk">
                        </th>
                        <th>ID</th>
                        <th width="50%">Name</th>
                        <th>Permissions</th>
                        <th>Status</th>
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
    <script src="{{ asset('assets/system/js/pages/roles.js') }}"></script>
    <script>
        $(function () {
            $(document).on('change', '#globalSelectAll', function () {
                let checked = $(this).prop('checked');

                $('.permission-chk').prop('checked', checked);
                $('.module-select-all-chk').prop('checked', checked);
            });

            // Module
            $(document).on('change', '.module-select-all-chk', function () {

                let module = $(this).data('module');
                let checked = $(this).prop('checked');

                $('.' + module).prop('checked', checked);

                $('#globalSelectAll').prop(
                    'checked',
                    $('.permission-chk').length === $('.permission-chk:checked').length
                );
            });

            // Individual
            $(document).on('change', '.permission-chk', function () {

                let module = $(this).data('module');

                let total = $('.' + module).length;
                let checked = $('.' + module + ':checked').length;

                $('.module-select-all-chk[data-module="' + module + '"]') .prop('checked', total === checked);

                $('#globalSelectAll').prop(
                    'checked',
                    $('.permission-chk').length === $('.permission-chk:checked').length
                );
            });

            // Initial State
            $('.module-select-all-chk').each(function(){

                let module = $(this).data('module');

                $(this).prop(
                    'checked',
                    $('.'+module).length === $('.'+module+':checked').length
                );
            });

            $('#globalSelectAll').prop(
                'checked',
                $('.permission-chk').length === $('.permission-chk:checked').length
            );
        });
    </script>
@endpush
