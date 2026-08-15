@extends('admin.layout.app', ['title' => 'Documentation'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Documentation</h2>
            <div class="sec-sub">A simple guide to using the HRM system</div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Module tabs -->
        <div class="col-lg-3">
            <div class="nx-card">
                <div class="nx-card-body p-2">
                    <div class="nav flex-column nav-pills" id="docTabs" role="tablist" aria-orientation="vertical">
                        @foreach($sections as $index => $section)
                            <button class="nav-link doc-tab-btn {{ $index === 0 ? 'active' : '' }} d-flex align-items-center gap-2 text-start"
                                    id="doc-tab-{{ $section['id'] }}"
                                    data-bs-toggle="pill"
                                    data-bs-target="#doc-pane-{{ $section['id'] }}"
                                    type="button"
                                    role="tab">
                                <i class="{{ $section['icon'] }}"></i>
                                <span>{{ $section['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Documentation content -->
        <div class="col-lg-9">
            <div class="nx-card">
                <div class="nx-card-body">
                    <div class="tab-content" id="docTabsContent">
                        @foreach($sections as $index => $section)
                            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="doc-pane-{{ $section['id'] }}" role="tabpanel">
                                <h3 class="mb-3">{{ $section['title'] }}</h3>
                                <div class="doc-body">
                                    {!! $section['body'] !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .doc-body h6 {
            margin-top: 1.25rem;
            margin-bottom: .5rem;
            font-weight: 600;
            color: var(--tx-1);
        }
        .doc-body p, .doc-body li {
            color: var(--tx-2);
            line-height: 1.7;
        }
        .doc-body ul {
            padding-left: 1.25rem;
        }
        #docTabs .nav-link {
            color: var(--tx-2);
            border-radius: 8px;
            padding: .6rem .75rem;
        }
        #docTabs .nav-link.active {
            background: var(--pri, #6567f5);
            color: #fff;
        }
    </style>
@endpush
