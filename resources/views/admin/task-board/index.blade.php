@php
    $statusMeta = [
        'todo' => ['label' => 'To Do', 'accent' => '#94a3b8', 'icon' => 'ri-inbox-line'],
        'in_progress' => ['label' => 'In Progress', 'accent' => '#3b82f6', 'icon' => 'ri-loader-4-line'],
        'review' => ['label' => 'Review', 'accent' => '#f59e0b', 'icon' => 'ri-search-eye-line'],
        'done' => ['label' => 'Done', 'accent' => '#22c55e', 'icon' => 'ri-check-double-line'],
        'cancelled' => ['label' => 'Cancelled', 'accent' => '#ef4444', 'icon' => 'ri-close-circle-line'],
    ];
    $priorityAccent = [
        'low' => '#94a3b8',
        'medium' => '#0ea5e9',
        'high' => '#f59e0b',
        'critical' => '#ef4444',
    ];
@endphp

@extends('admin.layout.app', ['title' => 'Task Board'])

@section('content')
    <form method="GET" action="{{ route('admin.task-board.index') }}" class="tl-toolbar tb-toolbar">
        <select name="project_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All Projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>

        <select name="assigned_to" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All Assignees</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" {{ request('assigned_to') == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }}</option>
            @endforeach
        </select>

        <select name="priority" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            @foreach ($priorities as $priority)
                <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
            @endforeach
        </select>

        @if (request('project_id') || request('assigned_to') || request('priority'))
            <a href="{{ route('admin.task-board.index') }}" class="tb-clear">
                <i class="ri-close-line"></i> Clear
            </a>
        @endif

        <div class="tb-stats">
            <span class="tb-stat"><b>{{ $totalTasks }}</b> tasks</span>
            <span class="tb-stat {{ $overdueTasks ? 'is-danger' : '' }}"><b>{{ $overdueTasks }}</b> overdue</span>
            <span class="tb-stat"><b>{{ $unassignedTasks }}</b> unassigned</span>
        </div>

        <div class="tl-spacer"></div>

        <a href="{{ route('admin.project-tasks.index') }}" class="btn-nx-outline">
            <i class="ri-list-check-2 me-1"></i>
            List View
        </a>
    </form>

    <div class="tb-board" id="taskBoard">
        @foreach ($statuses as $status)
            @php $meta = $statusMeta[$status] ?? ['label' => ucfirst($status), 'accent' => '#94a3b8', 'icon' => 'ri-circle-line']; @endphp
            <section class="tb-column" data-status="{{ $status }}" style="--tb-accent: {{ $meta['accent'] }}">
                <header class="tb-column-head">
                    <span class="tb-column-title">
                        <i class="{{ $meta['icon'] }}"></i>
                        {{ $meta['label'] }}
                    </span>
                    <span class="tb-count">{{ $columns[$status]->count() }}</span>
                </header>

                <div class="tb-column-body" data-status="{{ $status }}">
                    @forelse ($columns[$status] as $task)
                        @php $accent = $priorityAccent[$task->priority] ?? '#94a3b8'; @endphp
                        <article class="tb-card" draggable="true" data-id="{{ $task->id }}" style="--tb-priority: {{ $accent }}">
                            <div class="tb-card-top">
                                <span class="tb-code">{{ $task->task_code }}</span>
                                <span class="tb-priority">{{ ucfirst($task->priority) }}</span>
                            </div>

                            <h6 class="tb-card-title">{{ $task->title }}</h6>

                            <div class="tb-card-project">
                                <i class="ri-folder-3-line"></i>
                                {{ $task->project?->name ?? 'Project removed' }}
                                @if ($task->milestone)
                                    <span class="tb-chip">{{ $task->milestone->name }}</span>
                                @endif
                            </div>

                            @if ($task->progress_percent > 0)
                                <div class="tb-progress" title="{{ $task->progress_percent }}% complete">
                                    <span style="width: {{ $task->progress_percent }}%"></span>
                                </div>
                            @endif

                            <footer class="tb-card-foot">
                                <span class="tb-owner">
                                    @if ($task->assignedTo)
                                        <span class="tb-avatar">{{ strtoupper(substr($task->assignedTo->first_name, 0, 1) . substr($task->assignedTo->last_name, 0, 1)) }}</span>
                                        {{ $task->assignedTo->first_name }}
                                    @else
                                        <span class="tb-avatar is-empty"><i class="ri-user-line"></i></span>
                                        Unassigned
                                    @endif
                                </span>

                                @if ($task->due_date)
                                    <span class="tb-due {{ $task->is_overdue ? 'is-overdue' : '' }}">
                                        <i class="ri-calendar-line"></i>
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                @endif
                            </footer>

                            <a href="{{ route('admin.project-tasks.index') }}?search={{ urlencode($task->task_code) }}" class="tb-open" title="Open in list view">
                                <i class="ri-arrow-right-up-line"></i>
                            </a>
                        </article>
                    @empty
                        <p class="tb-empty">Nothing here yet</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <div class="tb-toast" id="taskBoardToast"></div>
@endsection

@push('styles')
    <style>
        .tb-toolbar {
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
        }
        .tb-clear {
            font-size: .8rem;
            color: var(--bs-secondary-color, #6b7280);
            text-decoration: none;
            white-space: nowrap;
        }
        .tb-clear:hover { color: var(--bs-body-color, #111); }

        .tb-stats {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
        }
        .tb-stat {
            font-size: .75rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .14);
            color: var(--bs-secondary-color, #64748b);
            white-space: nowrap;
        }
        .tb-stat b { color: var(--bs-body-color, #0f172a); }
        .tb-stat.is-danger { background: rgba(239, 68, 68, .12); color: #ef4444; }
        .tb-stat.is-danger b { color: #ef4444; }

        /* ---------- Board ---------- */
        .tb-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: .25rem .25rem 1.25rem;
            align-items: flex-start;
            scrollbar-width: thin;
        }
        .tb-board::-webkit-scrollbar { height: 8px; }
        .tb-board::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, .4);
            border-radius: 999px;
        }

        .tb-column {
            flex: 0 0 300px;
            max-width: 300px;
            background: rgba(148, 163, 184, .08);
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 14px;
            padding: .65rem;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 230px);
        }
        .tb-column-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .15rem .35rem .6rem;
            border-bottom: 2px solid var(--tb-accent);
            margin-bottom: .65rem;
        }
        .tb-column-title {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            font-size: .85rem;
            color: var(--bs-body-color, #0f172a);
        }
        .tb-column-title i { color: var(--tb-accent); font-size: 1rem; }
        .tb-count {
            font-size: .72rem;
            font-weight: 600;
            min-width: 22px;
            text-align: center;
            padding: .1rem .45rem;
            border-radius: 999px;
            color: var(--tb-accent);
            background: color-mix(in srgb, var(--tb-accent) 16%, transparent);
        }

        .tb-column-body {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            min-height: 90px;
            overflow-y: auto;
            padding: .15rem;
            border-radius: 10px;
            transition: background .15s ease, box-shadow .15s ease;
        }
        .tb-column-body.is-over {
            background: color-mix(in srgb, var(--tb-accent) 10%, transparent);
            box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--tb-accent) 45%, transparent);
        }

        /* ---------- Card ---------- */
        .tb-card {
            position: relative;
            background: var(--bs-body-bg, #fff);
            border: 1px solid rgba(148, 163, 184, .22);
            border-left: 3px solid var(--tb-priority);
            border-radius: 10px;
            padding: .6rem .7rem;
            cursor: grab;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
        }
        .tb-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, .1);
        }
        .tb-card.is-dragging { opacity: .45; cursor: grabbing; }
        .tb-card.is-saving { opacity: .6; pointer-events: none; }

        .tb-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .3rem;
        }
        .tb-code {
            font-size: .68rem;
            letter-spacing: .02em;
            color: var(--bs-secondary-color, #94a3b8);
        }
        .tb-priority {
            font-size: .65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .1rem .4rem;
            border-radius: 999px;
            color: var(--tb-priority);
            background: color-mix(in srgb, var(--tb-priority) 14%, transparent);
        }
        .tb-card-title {
            font-size: .84rem;
            font-weight: 600;
            line-height: 1.3;
            margin: 0 0 .35rem;
            color: var(--bs-body-color, #0f172a);
        }
        .tb-card-project {
            display: flex;
            align-items: center;
            gap: .3rem;
            flex-wrap: wrap;
            font-size: .72rem;
            color: var(--bs-secondary-color, #64748b);
            margin-bottom: .45rem;
        }
        .tb-chip {
            font-size: .66rem;
            padding: .05rem .4rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .18);
        }

        .tb-progress {
            height: 4px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .22);
            overflow: hidden;
            margin-bottom: .5rem;
        }
        .tb-progress span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: var(--tb-priority);
        }

        .tb-card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            font-size: .72rem;
            color: var(--bs-secondary-color, #64748b);
        }
        .tb-owner { display: inline-flex; align-items: center; gap: .35rem; }
        .tb-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .62rem;
            font-weight: 700;
            color: #fff;
            background: var(--tb-priority);
        }
        .tb-avatar.is-empty {
            background: rgba(148, 163, 184, .3);
            color: var(--bs-secondary-color, #64748b);
        }
        .tb-due { display: inline-flex; align-items: center; gap: .25rem; }
        .tb-due.is-overdue { color: #ef4444; font-weight: 600; }

        .tb-open {
            position: absolute;
            top: .45rem;
            right: .45rem;
            opacity: 0;
            color: var(--bs-secondary-color, #94a3b8);
            transition: opacity .12s ease;
        }
        .tb-card:hover .tb-open { opacity: 1; }

        .tb-empty {
            font-size: .75rem;
            color: var(--bs-secondary-color, #94a3b8);
            text-align: center;
            padding: 1rem .5rem;
            margin: 0;
            border: 1px dashed rgba(148, 163, 184, .35);
            border-radius: 10px;
        }

        /* ---------- Toast ---------- */
        .tb-toast {
            position: fixed;
            bottom: 1.25rem;
            left: 50%;
            transform: translate(-50%, 1rem);
            background: #0f172a;
            color: #fff;
            font-size: .8rem;
            padding: .55rem 1rem;
            border-radius: 999px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .25);
            opacity: 0;
            pointer-events: none;
            transition: opacity .18s ease, transform .18s ease;
            z-index: 1080;
        }
        .tb-toast.is-visible { opacity: 1; transform: translate(-50%, 0); }
        .tb-toast.is-error { background: #b91c1c; }

        @media (max-width: 767.98px) {
            .tb-column { flex-basis: 82vw; max-width: 82vw; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.taskBoardMoveUrlTemplate = "{{ route('admin.task-board.move', ['project_task' => '__ID__']) }}";
    </script>
    <script src="{{ asset('assets/system/js/pages/task-board.js') }}"></script>
@endpush
