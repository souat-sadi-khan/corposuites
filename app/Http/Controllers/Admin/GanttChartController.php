<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GanttChartController extends Controller
{
    /**
     * A read-only timeline over work that already exists.
     *
     * No new table, no Model, no Service, no Form Request — Projects,
     * Milestones and Tasks all carry their own dates; this schedules them on
     * one axis. Per this project's standing precedent each report controller
     * computes its own aggregation rather than sharing a service.
     *
     * Two modes, the same shape General Ledger established: with no project
     * selected it plots every project's own span; selecting one plots that
     * project's tasks, with its milestones marked on the axis.
     */
    public function index(Request $request)
    {
        $projectId = $request->project_id;
        $selectedProject = $projectId ? Project::with('client')->find($projectId) : null;

        $rows = $selectedProject
            ? $this->taskRows($selectedProject, $request)
            : $this->projectRows($request);

        $milestones = $selectedProject
            ? ProjectMilestone::active()->where('project_id', $selectedProject->id)->orderBy('due_date')->get()
            : collect();

        [$rangeStart, $rangeEnd] = $this->resolveRange($rows, $milestones);

        return view('admin.gantt-chart.index', [
            'projects' => Project::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'selectedProject' => $selectedProject,
            'rows' => $this->position($rows, $rangeStart, $rangeEnd),
            'milestoneMarkers' => $this->positionMilestones($milestones, $rangeStart, $rangeEnd),
            'months' => $this->monthSegments($rangeStart, $rangeEnd),
            'todayOffset' => $this->offsetFor(now()->startOfDay(), $rangeStart, $rangeEnd),
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'statuses' => ProjectTask::STATUSES,
        ]);
    }

    /**
     * Every project's own planned span, for the unscoped view.
     */
    protected function projectRows(Request $request): array
    {
        $query = Project::active()->with('client');

        if ($request->project_status) {
            $query->where('project_status', $request->project_status);
        }

        return $query->orderBy('start_date')->get()
            ->map(function (Project $project) {
                return [
                    'id' => $project->id,
                    'label' => $project->name,
                    'sub' => $project->project_code . ($project->client ? ' · ' . $project->client->name : ''),
                    'start' => $project->start_date,
                    'end' => $project->end_date ?? $project->actual_end_date ?? $project->start_date,
                    'has_end' => (bool) ($project->end_date ?? $project->actual_end_date),
                    'progress' => (int) $project->progress_percent,
                    'state' => $project->project_status,
                    'state_label' => $project->project_status_label,
                    'overdue' => $project->is_overdue,
                    'owner' => $project->projectManager
                        ? $project->projectManager->first_name . ' ' . $project->projectManager->last_name
                        : null,
                    'link' => route('admin.gantt-chart.index', ['project_id' => $project->id]),
                ];
            })->all();
    }

    /**
     * One row per task of the selected project.
     *
     * A task with no dates cannot be plotted, so it is left out of the chart
     * and counted separately — reported on screen rather than silently
     * dropped, the same approach Stock Valuation takes with missing costs.
     */
    protected function taskRows(Project $project, Request $request): array
    {
        $query = ProjectTask::active()->with(['assignedTo', 'milestone'])
            ->where('project_id', $project->id);

        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->task_status) {
            $query->where('task_status', $request->task_status);
        }

        return $query->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (ProjectTask $task) {
                $start = $task->start_date ?? $task->due_date;
                $end = $task->due_date ?? $task->start_date;

                return [
                    'id' => $task->id,
                    'label' => $task->title,
                    'sub' => $task->task_code . ($task->milestone ? ' · ' . $task->milestone->name : ''),
                    'start' => $start,
                    'end' => $end,
                    'has_end' => (bool) $task->due_date,
                    'progress' => (int) $task->progress_percent,
                    'state' => $task->task_status,
                    'state_label' => $task->task_status_label,
                    'overdue' => $task->is_overdue,
                    'owner' => $task->assignedTo
                        ? $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name
                        : null,
                    'link' => null,
                ];
            })->all();
    }

    /**
     * The axis: the span the rows and milestones actually cover, padded a
     * little at each end so bars do not sit flush against the frame.
     */
    protected function resolveRange(array $rows, $milestones): array
    {
        $dates = collect($rows)
            ->flatMap(fn ($row) => [$row['start'], $row['end']])
            ->merge($milestones->pluck('due_date'))
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->startOfDay());

        if ($dates->isEmpty()) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        // Both ends are normalised to midnight so every span in this class is
        // counted in whole, inclusive days — mixing a 23:59 end date in here
        // makes the month blocks overrun the axis.
        $start = $dates->min()->copy()->startOfMonth()->startOfDay();
        $end = $dates->max()->copy()->endOfMonth()->startOfDay();

        // A single-day span would divide by zero when positioning bars.
        if ($start->equalTo($end)) {
            $end = $end->copy()->addMonth()->endOfMonth()->startOfDay();
        }

        return [$start, $end];
    }

    /**
     * The axis length in whole days, inclusive of both ends — the single
     * divisor every offset and width in this class is measured against.
     */
    protected function totalDays(Carbon $start, Carbon $end): int
    {
        return max(1, (int) $start->diffInDays($end) + 1);
    }

    /**
     * Percentage offset of a date along the axis, clamped to the frame.
     */
    protected function offsetFor(?Carbon $date, Carbon $start, Carbon $end): ?float
    {
        if (! $date) {
            return null;
        }

        $total = $this->totalDays($start, $end);
        $offset = (int) $start->diffInDays($date->copy()->startOfDay(), false);

        if ($offset < 0 || $offset >= $total) {
            return null; // outside the plotted range — nothing to draw
        }

        return round(($offset / $total) * 100, 3);
    }

    protected function position(array $rows, Carbon $start, Carbon $end): array
    {
        $total = $this->totalDays($start, $end);

        return collect($rows)->map(function ($row) use ($start, $total) {
            $rowStart = $row['start'] ? Carbon::parse($row['start'])->startOfDay() : null;
            $rowEnd = $row['end'] ? Carbon::parse($row['end'])->startOfDay() : null;

            if (! $rowStart || ! $rowEnd) {
                $row['plottable'] = false;

                return $row;
            }

            if ($rowEnd->lessThan($rowStart)) {
                $rowEnd = $rowStart->copy();
            }

            $offset = max(0, (int) $start->diffInDays($rowStart, false));
            // Inclusive of both ends, so a same-day item is still a visible bar.
            $length = max(1, (int) $rowStart->diffInDays($rowEnd) + 1);

            $row['plottable'] = true;
            $row['offset'] = round(($offset / $total) * 100, 3);
            $row['width'] = round((min($length, $total - $offset) / $total) * 100, 3);
            $row['days'] = $length;

            return $row;
        })->all();
    }

    protected function positionMilestones($milestones, Carbon $start, Carbon $end): array
    {
        return $milestones->map(function (ProjectMilestone $milestone) use ($start, $end) {
            return [
                'name' => $milestone->name,
                'date' => $milestone->due_date,
                'state' => $milestone->milestone_status,
                'overdue' => $milestone->is_overdue,
                'offset' => $this->offsetFor($milestone->due_date->copy()->startOfDay(), $start, $end),
            ];
        })->filter(fn ($marker) => $marker['offset'] !== null)->values()->all();
    }

    /**
     * Month blocks across the header, each sized by its share of the axis.
     */
    protected function monthSegments(Carbon $start, Carbon $end): array
    {
        $total = $this->totalDays($start, $end);
        $segments = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $monthStart = $cursor->copy()->startOfDay()->max($start);
            $monthEnd = $cursor->copy()->endOfMonth()->startOfDay()->min($end);
            $days = max(1, (int) $monthStart->diffInDays($monthEnd) + 1);

            $segments[] = [
                'label' => $cursor->format('M Y'),
                'short' => $cursor->format('M'),
                'width' => round(($days / $total) * 100, 3),
            ];

            $cursor->addMonth()->startOfMonth();
        }

        return $segments;
    }
}
