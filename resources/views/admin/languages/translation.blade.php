@extends('admin.layout.app', [
    'title' => 'Translation Management',
])

@section('content')

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.languages.index') }}">
                            Languages
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        Translation Management
                    </li>
                </ol>
            </nav>

            <h4 class="mb-1 fw-semibold">
                {{ $language->name }}
                <small class="text-muted">
                    ({{ strtoupper($language->code) }})
                </small>
            </h4>

            <div class="text-muted small">
                Manage language translations
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3">
            <div class="nx-card h-100">
                <div class="card-body">
                    <div class="small text-muted mb-1">
                        Language
                    </div>

                    <h5 class="mb-0">
                        {{ $language->name }}
                    </h5>

                    <div class="text-muted">
                        {{ $language->native_name }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2">
            <div class="nx-card h-100">
                <div class="card-body">
                    <div class="small text-muted">
                        Code
                    </div>

                    <div class="fs-4 fw-semibold">
                        {{ strtoupper($language->code) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2">
            <div class="nx-card h-100">
                <div class="card-body">
                    <div class="small text-muted">
                        Direction
                    </div>

                    <div class="fs-6 fw-semibold text-uppercase">
                        {{ $language->direction }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2">
            <div class="nx-card h-100">
                <div class="card-body">
                    <div class="small text-muted">
                        Status
                    </div>

                    @if($language->is_active)
                        <span class="badge bg-success">
                            Active
                        </span>
                    @else
                        <span class="badge bg-danger">
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="nx-card h-100">
                <div class="card-body">

                    <div class="small text-muted mb-2">
                        Translation Progress
                    </div>

                    @php
                        $total = $translations->count();
                        $completed = $translations->whereNotNull('value')->where('value','!=','')->count();
                        $percent = $total ? round(($completed/$total)*100) : 0;
                    @endphp

                    <div class="d-flex justify-content-between mb-2">
                        <strong>
                            {{ $completed }}
                            /
                            {{ $total }}
                        </strong>

                        <strong>
                            {{ $percent }}%
                        </strong>
                    </div>

                    <div class="progress" style="height:8px">
                        <div
                            class="progress-bar"
                            role="progressbar"
                            style="width: {{ $percent }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tl-toolbar ">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="translationSearch" placeholder="Search translation...">
        </div>

        <div class="tl-spacer"></div>

        <div class="col-md-3 fm-body">
            <select id="groupFilter" class="form-control select" style="min-width:220px">
                <option value="">
                    All Groups
                </option>

                @foreach($groups as $group)
                    <option value="{{ $group->id }}">
                        {{ $group->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 fm-body">
            <div class="form-field">
                <select id="statusFilter" class="form-control select" style="width:180px">
                    <option value=""> All </option>
                    <option value="translated"> Translated </option>
                    <option value="pending"> Pending </option>
                </select>
            </div>
        </div>
    </div>

    {{-- ===========================
        Translation Table
    ============================ --}}

    <form action="{{ route('admin.languages.translations.update', $language) }}" method="POST" class="ajax_form">
        <div class="nx-card">
            <div class="table-responsive">
                <table class="tl-table align-middle">
                    <thead>
                        <tr>
                            <th width="20%">
                                Group
                            </th>
                            <th width="20%">
                                Key
                            </th>
                            <th width="25%">
                                English
                            </th>
                            <th width="35%">
                                Translation
                            </th>
                        </tr>
                    </thead>
                    <tbody id="translationTable">
                        @forelse($translations as $translation)
                            @php
                                $key = $translation->translationKey;

                                $english = $key->english ?? $key->value ?? '';

                                $status = filled($translation->value);

                                $groupId = $key->group_id ?? '';

                                $groupName = optional($key->group)->name ?? '-';
                            @endphp

                            <tr data-group="{{ $groupId }}" data-status="{{ $status ? 'translated' : 'pending' }}" >
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $groupName }}
                                    </span>
                                </td>

                                <td>
                                    <code>{{ $key->key }}</code>
                                </td>

                                <td>
                                    <div class="fw-medium">
                                        {{ $english }}
                                    </div>

                                    <small class="text-muted">
                                        {{ $key->key }}
                                    </small>
                                </td>

                                <td class="fm-body">
                                    <textarea class="form-control translation-input" name="translations[{{ $translation->id }}]" rows="2" placeholder="Enter translation..." >{{ old("translations.$translation->id", $translation->value) }}</textarea>

                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        @if($status)
                                            <span class="badge bg-success">
                                                Translated
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                Pending
                                            </span>
                                        @endif

                                        <small class="text-muted">
                                            {{ $translation->updated_at?->diffForHumans() }}
                                        </small>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="ri-translate-2 fs-1 text-muted"></i>
                                    <div class="mt-3 text-muted">
                                        No translation keys found.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Total Translations :
                        <strong>{{ $translations->count() }}</strong>
                    </div>

                    <button type="submit" class="btn-nx-primary" >
                        <i class="ri-save-line me-2"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
    <script>
        $(function () {

            _ajaxFormHandler('.ajax_form');
            _componentSelect();

            function filterTranslations() {

                let searchText = $('#translationSearch').val().toLowerCase().trim();
                let group = $('#groupFilter').val();
                let status = $('#statusFilter').val();

                $('#translationTable tr').each(function () {

                    let row = $(this);

                    let rowText = row.text().toLowerCase();

                    let rowGroup = String(row.data('group'));
                    let rowStatus = String(row.data('status'));

                    let matchSearch = true;
                    let matchGroup = true;
                    let matchStatus = true;

                    // Search Filter
                    if (searchText !== '') {
                        matchSearch = rowText.indexOf(searchText) !== -1;
                    }

                    // Group Filter
                    if (group !== '' && group !== null) {
                        matchGroup = rowGroup === group;
                    }

                    // Status Filter
                    if (status !== '' && status !== null) {
                        matchStatus = rowStatus === status;
                    }

                    row.toggle(
                        matchSearch &&
                        matchGroup &&
                        matchStatus
                    );
                });

                updateVisibleCount();
            }

            function updateVisibleCount() {
                let visibleRows = $('#translationTable tr:visible').length;

                $('.translation-visible-count').remove();

                $('.card-footer .text-muted').append(
                    ' <span class="translation-visible-count">(Showing: ' +
                    visibleRows +
                    ')</span>'
                );
            }

            // Search
            $('#translationSearch').on('keyup', function () {
                filterTranslations();
            });

            // Group Filter
            $('#groupFilter').on('change.select2 change', function () {
                filterTranslations();
            });

            // Status Filter
            $('#statusFilter').on('change.select2 change', function () {
                filterTranslations();
            });

            // Translation change হলে status update
            $('.translation-input').on('input', function () {

                let row = $(this).closest('tr');

                if ($(this).val().trim() !== '') {
                    row.attr('data-status', 'translated');
                    row.data('status', 'translated');
                } else {
                    row.attr('data-status', 'pending');
                    row.data('status', 'pending');
                }

                filterTranslations();
            });

            filterTranslations();
        });
    </script>
@endpush
