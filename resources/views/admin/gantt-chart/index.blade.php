@php
    $stateAccent = [
        // task states
        'todo' => '#94a3b8',
        'in_progress' => '#3b82f6',
        'review' => '#f59e0b',
        'done' => '#22c55e',
        'cancelled' => '#ef4444',
        // project states
        'planned' => '#94a3b8',
        'on_hold' => '#f59e0b',
        'completed' => '#22c55e',
    ];
    $plottable = collect($rows)->where('plottable', true);
    $unplottable = collect($rows)->where('plottable', false);
@endphp

@extends('admin.layout.app', ['title' => 'Gantt Chart'])

@section('content')
    <form method="GET" action="{{ route('admin.gantt-chart.index') }}" class="tl-toolbar gt-toolbar">
        <select name="project_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All Projects (overview)</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>

        @if ($selectedProject)
            <select name="assigned_to" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Assignees</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('assigned_to') == $employee->id ? 'selected' : '' }}>{{ $employee->first_name }} {{ $employee->last_name }}</option>
                @endforeach
            </select>

            <select name="task_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Task States</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" {{ request('task_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        @else
            <select name="project_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Project States</option>
                @foreach (\App\Models\Project::STATUSES as $projectStatus)
                    <option value="{{ $projectStatus }}" {{ request('project_status') === $projectStatus ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $projectStatus)) }}</option>
                @endforeach
            </select>
        @endif

        @if (request()->hasAny(['project_id', 'assigned_to', 'task_status', 'project_status']))
            <a href="{{ route('admin.gantt-chart.index') }}" class="gt-clear"><i class="ri-close-line"></i> Clear</a>
        @endif

        <div class="gt-range">
            <i class="ri-calendar-2-line"></i>
            {{ $rangeStart->format('d M Y') }} — {{ $rangeEnd->format('d M Y') }}
        </div>

        <div class="tl-spacer"></div>

        @if ($selectedProject)
            <a href="{{ route('admin.project-tasks.index') }}" class="btn-nx-outline">
                <i class="ri-list-check-2 me-1"></i> Task List
            </a>
        @endif
    </form>

    <div class="nx-card gt-card">
        <div class="gt-head">
            <div>
                <h6 class="gt-title">
                    {{ $selectedProject ? $selectedProject->name : 'All Projects' }}
                </h6>
                <p class="gt-sub">
                    {{ $selectedProject
                        ? $selectedProject->project_code . ($selectedProject->client ? ' · ' . $selectedProject->client->name : '') . ' — tasks on the timeline'
                        : 'Every active project plotted on one axis — pick a project to drill into its tasks' }}
                </p>
            </div>

            <div class="gt-legend">
                @foreach (($selectedProject ? $statuses : \App\Models\Project::STATUSES) as $state)
                    <span class="gt-legend-item">
                        <i style="background: {{ $stateAccent[$state] ?? '#94a3b8' }}"></i>
                        {{ ucwords(str_replace('_', ' ', $state)) }}
                    </span>
                @endforeach
            </div>
        </div>

        @if ($plottable->isEmpty())
            <p class="gt-empty">
                <i class="ri-calendar-close-line"></i>
                {{ $selectedProject ? 'No dated tasks to plot for this project yet.' : 'No active projects to plot yet.' }}
            </p>
        @else
            <div class="gt-scroll">
                <div class="gt-grid">
                    <!-- Header -->
                    <div class="gt-row gt-row-head">
                        <div class="gt-label gt-label-head">{{ $selectedProject ? 'Task' : 'Project' }}</div>
                        <div class="gt-track gt-track-head">
                            @foreach ($months as $month)
                                <div class="gt-month" style="width: {{ $month['width'] }}%">
                                    <span>{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Milestone axis -->
                    @if (count($milestoneMarkers))
                        <div class="gt-row gt-row-milestones">
                            <div class="gt-label gt-label-muted">
                                <i class="ri-flag-2-line"></i> Milestones
                            </div>
                            <div class="gt-track">
                                @foreach ($months as $month)
                                    <span class="gt-gridline" style="width: {{ $month['width'] }}%"></span>
                                @endforeach

                                @if ($todayOffset !== null)
                                    <span class="gt-today" style="left: {{ $todayOffset }}%"></span>
                                @endif

                                @foreach ($milestoneMarkers as $marker)
                                    <span class="gt-milestone {{ $marker['overdue'] ? 'is-overdue' : '' }} {{ $marker['state'] === 'completed' ? 'is-done' : '' }}"
                                          style="left: {{ $marker['offset'] }}%"
                                          title="{{ $marker['name'] }} — {{ $marker['date']->format('d M Y') }}">
                                        <b>{{ $marker['name'] }}</b>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Rows -->
                    @foreach ($plottable as $row)
                        @php $accent = $stateAccent[$row['state']] ?? '#94a3b8'; @endphp
                        <div class="gt-row">
                            <div class="gt-label">
                                @if ($row['link'])
                                    <a href="{{ $row['link'] }}" class="gt-label-name">{{ $row['label'] }}</a>
                                @else
                                    <span class="gt-label-name">{{ $row['label'] }}</span>
                                @endif
                                <small>{{ $row['sub'] }}</small>
                            </div>

                            <div class="gt-track">
                                @foreach ($months as $month)
                                    <span class="gt-gridline" style="width: {{ $month['width'] }}%"></span>
                                @endforeach

                                @if ($todayOffset !== null)
                                    <span class="gt-today" style="left: {{ $todayOffset }}%"></span>
                                @endif

                                <div class="gt-bar {{ $row['overdue'] ? 'is-overdue' : '' }} {{ $row['has_end'] ? '' : 'is-open-ended' }}"
                                     style="left: {{ $row['offset'] }}%; width: {{ $row['width'] }}%; --gt-accent: {{ $accent }}"
                                     title="{{ $row['label'] }} — {{ \Carbon\Carbon::parse($row['start'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($row['end'])->format('d M Y') }} ({{ $row['days'] }} {{ \Illuminate\Support\Str::plural('day', $row['days']) }})">
                                    @if ($row['progress'] > 0)
                                        <span class="gt-bar-fill" style="width: {{ $row['progress'] }}%"></span>
                                    @endif
                                    <span class="gt-bar-text">
                                        {{ $row['days'] }}d
                                        @if ($row['progress'] > 0) · {{ $row['progress'] }}% @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="gt-foot">
                <span><i class="ri-time-line"></i> Today marked by the vertical line</span>
                <span>{{ $plottable->count() }} {{ \Illuminate\Support\Str::plural('bar', $plottable->count()) }} plotted</span>
            </div>
        @endif

        @if ($unplottable->isNotEmpty())
            <div class="gt-unplotted">
                <b><i class="ri-error-warning-line"></i> {{ $unplottable->count() }} {{ \Illuminate\Support\Str::plural('item', $unplottable->count()) }} not on the chart</b>
                <span>No start or due date recorded, so there is nothing to plot:</span>
                <ul>
                    @foreach ($unplottable as $row)
                        <li>{{ $row['label'] }} <small>{{ $row['sub'] }}</small></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .gt-toolbar { flex-wrap: wrap; gap: .5rem; align-items: center; }
        .gt-clear {
            font-size: .8rem;
            color: var(--bs-secondary-color, #6b7280);
            text-decoration: none;
            white-space: nowrap;
        }
        .gt-clear:hover { color: var(--bs-body-color, #111); }
        .gt-range {
            font-size: .75rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .14);
            color: var(--bs-secondary-color, #64748b);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            white-space: nowrap;
        }

        .gt-card { padding: 1rem 1.1rem 1.1rem; }
        .gt-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: .9rem;
        }
        .gt-title { margin: 0; font-size: .95rem; font-weight: 600; }
        .gt-sub { margin: .15rem 0 0; font-size: .76rem; color: var(--bs-secondary-color, #64748b); }
        .gt-legend { display: flex; flex-wrap: wrap; gap: .6rem; }
        .gt-legend-item {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .7rem;
            color: var(--bs-secondary-color, #64748b);
        }
        .gt-legend-item i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }

        .gt-scroll { overflow-x: auto; scrollbar-width: thin; }
        .gt-scroll::-webkit-scrollbar { height: 8px; }
        .gt-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, .4); border-radius: 999px; }
        .gt-grid { min-width: 780px; }

        .gt-row {
            display: flex;
            align-items: stretch;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
        }
        .gt-row:last-child { border-bottom: 0; }
        .gt-row:not(.gt-row-head):hover { background: rgba(148, 163, 184, .06); }

        .gt-label {
            flex: 0 0 220px;
            width: 220px;
            padding: .5rem .7rem .5rem .2rem;
            border-right: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .1rem;
            position: sticky;
            left: 0;
            background: var(--bs-body-bg, #fff);
            z-index: 2;
        }
        .gt-label-name {
            font-size: .8rem;
            font-weight: 600;
            color: var(--bs-body-color, #0f172a);
            text-decoration: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        a.gt-label-name:hover { color: var(--bs-primary, #3b82f6); }
        .gt-label small { font-size: .68rem; color: var(--bs-secondary-color, #94a3b8); }
        .gt-label-head, .gt-label-muted {
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--bs-secondary-color, #94a3b8);
        }
        .gt-label-muted { flex-direction: row; align-items: center; gap: .3rem; text-transform: none; letter-spacing: 0; }

        .gt-track {
            position: relative;
            flex: 1 1 auto;
            min-height: 44px;
            display: flex;
        }
        .gt-track-head { min-height: 34px; }
        .gt-gridline {
            border-right: 1px dashed rgba(148, 163, 184, .22);
            height: 100%;
            display: block;
        }
        .gt-gridline:last-child { border-right: 0; }

        .gt-month {
            border-right: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .gt-month:last-child { border-right: 0; }
        .gt-month span {
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: .03em;
            color: var(--bs-secondary-color, #64748b);
            white-space: nowrap;
        }

        .gt-today {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ef4444;
            opacity: .55;
            z-index: 1;
        }

        .gt-bar {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            height: 22px;
            min-width: 6px;
            border-radius: 6px;
            background: color-mix(in srgb, var(--gt-accent) 26%, transparent);
            border: 1px solid var(--gt-accent);
            overflow: hidden;
            z-index: 2;
            display: flex;
            align-items: center;
            transition: filter .12s ease, transform .12s ease;
        }
        .gt-bar:hover { filter: brightness(1.03); transform: translateY(-50%) scale(1.01); }
        .gt-bar.is-overdue { border-color: #ef4444; box-shadow: 0 0 0 1px rgba(239, 68, 68, .35); }
        .gt-bar.is-open-ended {
            border-style: dashed;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .gt-bar-fill {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            background: var(--gt-accent);
            opacity: .85;
        }
        .gt-bar-text {
            position: relative;
            z-index: 1;
            font-size: .64rem;
            font-weight: 600;
            padding: 0 .35rem;
            white-space: nowrap;
            color: var(--bs-body-color, #0f172a);
            mix-blend-mode: luminosity;
        }

        .gt-row-milestones { background: rgba(148, 163, 184, .05); }
        .gt-milestone {
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .15rem;
        }
        .gt-milestone::before {
            content: '';
            width: 11px;
            height: 11px;
            background: #6366f1;
            transform: rotate(45deg);
            border-radius: 2px;
        }
        .gt-milestone.is-done::before { background: #22c55e; }
        .gt-milestone.is-overdue::before { background: #ef4444; }
        .gt-milestone b {
            font-size: .62rem;
            font-weight: 600;
            color: var(--bs-secondary-color, #64748b);
            white-space: nowrap;
            max-width: 110px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .gt-foot {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            font-size: .7rem;
            color: var(--bs-secondary-color, #94a3b8);
            padding-top: .65rem;
            margin-top: .3rem;
            border-top: 1px solid rgba(148, 163, 184, .16);
        }
        .gt-foot i { color: #ef4444; }

        .gt-empty {
            text-align: center;
            font-size: .8rem;
            color: var(--bs-secondary-color, #94a3b8);
            padding: 2rem 1rem;
            margin: 0;
            border: 1px dashed rgba(148, 163, 184, .35);
            border-radius: 12px;
        }
        .gt-empty i { display: block; font-size: 1.6rem; margin-bottom: .4rem; }

        .gt-unplotted {
            margin-top: .9rem;
            padding: .7rem .85rem;
            border-radius: 10px;
            background: rgba(245, 158, 11, .1);
            border: 1px solid rgba(245, 158, 11, .3);
            font-size: .74rem;
            color: var(--bs-body-color, #0f172a);
        }
        .gt-unplotted b { display: block; margin-bottom: .15rem; color: #b45309; }
        .gt-unplotted span { color: var(--bs-secondary-color, #64748b); }
        .gt-unplotted ul { margin: .35rem 0 0; padding-left: 1.1rem; }
        .gt-unplotted li { margin-bottom: .1rem; }
        .gt-unplotted small { color: var(--bs-secondary-color, #94a3b8); }

        @media (max-width: 767.98px) {
            .gt-label { flex-basis: 150px; width: 150px; }
        }
    </style>
@endpush
