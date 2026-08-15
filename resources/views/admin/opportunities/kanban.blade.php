@extends('admin.layout.app', ['title' => 'Sales Pipeline'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-spacer"></div>

        <a href="{{ route('admin.opportunities.index') }}" class="btn-nx-outline">
            <i class="ri-list-check-2 me-1"></i>
            List View
        </a>
    </div>

    <div class="kanban-board" id="kanbanBoard">
        @foreach($stages as $stage)
            <div class="kanban-column" data-stage="{{ $stage }}">
                <div class="kanban-column-head">
                    <span>{{ ucfirst($stage) }}</span>
                    <span class="badge bg-secondary">{{ isset($opportunities[$stage]) ? count($opportunities[$stage]) : 0 }}</span>
                </div>
                <div class="kanban-column-body" data-stage="{{ $stage }}">
                    @foreach($opportunities[$stage] ?? [] as $opportunity)
                        <div class="kanban-card" draggable="true" data-id="{{ $opportunity->id }}">
                            <div class="kanban-card-title">{{ $opportunity->name }}</div>
                            <div class="kanban-card-sub">{{ $opportunity->company->name ?? '-' }}</div>
                            <div class="kanban-card-meta">
                                <span>{{ $opportunity->amount !== null ? number_format($opportunity->amount, 2) : '-' }}</span>
                                <span>{{ $opportunity->assignedTo->name ?? 'Unassigned' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('styles')
    <style>
        .kanban-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .kanban-column {
            flex: 0 0 260px;
            background: var(--bs-tertiary-bg, #f5f5f5);
            border-radius: 8px;
            padding: 0.5rem;
        }
        .kanban-column-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            padding: 0.5rem;
        }
        .kanban-column-body {
            min-height: 120px;
        }
        .kanban-card {
            background: #fff;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: grab;
        }
        .kanban-card.dragging {
            opacity: 0.5;
        }
        .kanban-card-title {
            font-weight: 600;
        }
        .kanban-card-sub {
            font-size: 0.8rem;
            color: #888;
        }
        .kanban-card-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.5rem;
        }
        .kanban-column-body.drag-over {
            background: rgba(0,0,0,0.05);
            border-radius: 6px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.opportunityMoveStageUrlTemplate = "{{ route('admin.opportunities.move-stage', ['opportunity' => '__ID__']) }}";
    </script>
    <script src="{{ asset('assets/system/js/pages/opportunities-kanban.js') }}"></script>
@endpush
