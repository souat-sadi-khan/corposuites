<?php

namespace App\Services;

use App\Models\ProjectTimeEntry;
use RuntimeException;

class ProjectTimeEntryService
{
    public function create(array $data): ProjectTimeEntry
    {
        return ProjectTimeEntry::create($this->withComputedHours($data));
    }

    public function update(ProjectTimeEntry $entry, array $data): ProjectTimeEntry
    {
        $this->guardNotLocked($entry);

        $entry->update($this->withComputedHours($data));

        return $entry->fresh();
    }

    public function delete(ProjectTimeEntry $entry): bool
    {
        $this->guardNotLocked($entry);

        return $entry->delete();
    }

    /**
     * An entry locks for either of two independent reasons: its timesheet
     * has been submitted for approval, or it has been billed on a Project
     * Invoice that hasn't been cancelled (see ProjectTimeEntry::is_locked).
     * Either way, editing or deleting it afterward would let the real
     * hours worked drift away from what was actually reviewed or billed.
     * The message names whichever reason actually applies — a generic
     * "part of a submitted timesheet" message would have been wrong (and
     * confusing) for an entry locked by invoicing instead, which is
     * exactly the case Project Billing's own verification caught.
     * Enforced here, not only hidden in the UI, so it holds regardless of
     * which screen an edit is attempted from.
     */
    protected function guardNotLocked(ProjectTimeEntry $entry): void
    {
        if (! $entry->is_locked) {
            return;
        }

        $timesheetLocked = $entry->projectTimesheet
            && in_array($entry->projectTimesheet->timesheet_status, \App\Models\ProjectTimesheet::LOCKED_STATUSES, true);

        if ($timesheetLocked) {
            throw new RuntimeException('This time entry is part of a submitted timesheet and can no longer be edited or deleted.');
        }

        throw new RuntimeException('This time entry has already been billed to the client and can no longer be edited or deleted.');
    }

    /**
     * Start a live timer for an employee. Only one may run at a time per
     * employee — a second start attempt is rejected rather than silently
     * abandoning the first, which would leave it running forever with no
     * way back to it from the quick-start widget.
     */
    public function startTimer(int $employeeId, int $projectId, ?int $projectTaskId, ?bool $isBillable = true): ProjectTimeEntry
    {
        if (ProjectTimeEntry::running()->where('employee_id', $employeeId)->exists()) {
            throw new RuntimeException('A timer is already running for this employee. Stop it before starting another.');
        }

        return ProjectTimeEntry::create([
            'employee_id' => $employeeId,
            'project_id' => $projectId,
            'project_task_id' => $projectTaskId,
            'work_date' => now()->toDateString(),
            'started_at' => now(),
            'ended_at' => null,
            'hours' => null,
            'is_billable' => $isBillable ?? true,
            'status' => true,
        ]);
    }

    /**
     * Stop a running timer, computing its hours from the elapsed time.
     */
    public function stopTimer(ProjectTimeEntry $entry, ?string $description = null): ProjectTimeEntry
    {
        if (! $entry->is_running) {
            throw new RuntimeException('This entry is not a running timer.');
        }

        $endedAt = now();

        $entry->update([
            'ended_at' => $endedAt,
            'hours' => $this->calculateHours($entry->started_at, $endedAt),
            'description' => $description ?? $entry->description,
        ]);

        return $entry->fresh();
    }

    /**
     * Clock timestamps, when both are present, are the source of truth for
     * `hours` — recomputed on every save so a manually-typed figure can
     * never drift from what the timer actually recorded. Without both
     * timestamps, whatever duration was submitted is kept as-is (a manual,
     * no-clock-times entry).
     */
    protected function withComputedHours(array $data): array
    {
        if (! empty($data['started_at']) && ! empty($data['ended_at'])) {
            $data['hours'] = $this->calculateHours($data['started_at'], $data['ended_at']);
        }

        return $data;
    }

    protected function calculateHours($startedAt, $endedAt): float
    {
        $start = $startedAt instanceof \Carbon\Carbon ? $startedAt : \Carbon\Carbon::parse($startedAt);
        $end = $endedAt instanceof \Carbon\Carbon ? $endedAt : \Carbon\Carbon::parse($endedAt);

        return round($start->diffInMinutes($end) / 60, 2);
    }
}
