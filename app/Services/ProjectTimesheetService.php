<?php

namespace App\Services;

use App\Models\ProjectTimeEntry;
use App\Models\ProjectTimesheet;
use Carbon\Carbon;
use RuntimeException;

class ProjectTimesheetService
{
    /**
     * Find-or-create the draft header for an employee's week (any date
     * inside the week is accepted and normalized to that week's Monday—
     * Sunday range), then link every finished Time Entry in that range
     * onto it and re-sum the totals. Safe to call again later to pull in
     * entries added or corrected after the first generation, as long as
     * the timesheet hasn't moved past draft/rejected yet.
     */
    public function generate(int $employeeId, string $anyDateInWeek): ProjectTimesheet
    {
        $weekStart = Carbon::parse($anyDateInWeek)->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $timesheet = ProjectTimesheet::firstOrNew([
            'employee_id' => $employeeId,
            'week_start_date' => $weekStart->toDateString(),
        ]);

        if ($timesheet->exists && ! in_array($timesheet->timesheet_status, ['draft', 'rejected'], true)) {
            throw new RuntimeException('This timesheet has already been ' . $timesheet->timesheet_status . ' and can no longer be regenerated.');
        }

        $timesheet->week_end_date = $weekEnd->toDateString();
        $timesheet->timesheet_status = 'draft';
        $timesheet->rejection_reason = null;
        $timesheet->save();

        // Only finished entries can be reviewed — a still-running timer has
        // no real hours yet, so it is left out until it's stopped and this
        // is regenerated.
        $entries = ProjectTimeEntry::where('employee_id', $employeeId)
            ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where(fn ($q) => $q->whereNull('started_at')->orWhereNotNull('ended_at'))
            ->where(fn ($q) => $q->whereNull('project_timesheet_id')->orWhere('project_timesheet_id', $timesheet->id))
            ->get();

        ProjectTimeEntry::whereIn('id', $entries->pluck('id'))->update(['project_timesheet_id' => $timesheet->id]);

        $timesheet->total_hours = $entries->sum('hours');
        $timesheet->billable_hours = $entries->where('is_billable', true)->sum('hours');
        $timesheet->save();

        return $timesheet->fresh();
    }

    public function submit(ProjectTimesheet $timesheet): ProjectTimesheet
    {
        if ($timesheet->timesheet_status !== 'draft') {
            throw new RuntimeException('Only a draft timesheet can be submitted.');
        }

        if ((float) $timesheet->total_hours <= 0) {
            throw new RuntimeException('This week has no logged hours yet — nothing to submit.');
        }

        $timesheet->update([
            'timesheet_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $timesheet->fresh();
    }

    public function approve(ProjectTimesheet $timesheet, int $adminId): ProjectTimesheet
    {
        if ($timesheet->timesheet_status !== 'submitted') {
            throw new RuntimeException('Only a submitted timesheet can be approved.');
        }

        $timesheet->update([
            'timesheet_status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);

        return $timesheet->fresh();
    }

    public function reject(ProjectTimesheet $timesheet, string $reason): ProjectTimesheet
    {
        if ($timesheet->timesheet_status !== 'submitted') {
            throw new RuntimeException('Only a submitted timesheet can be rejected.');
        }

        $timesheet->update([
            'timesheet_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $timesheet->fresh();
    }

    /**
     * Notes and the archive status toggle are the only fields a plain edit
     * form ever submits — everything else (dates, hours, workflow state) is
     * managed through generate()/submit()/approve()/reject() instead.
     */
    public function update(ProjectTimesheet $timesheet, array $data): ProjectTimesheet
    {
        $timesheet->update($data);

        return $timesheet->fresh();
    }

    public function delete(ProjectTimesheet $timesheet): bool
    {
        return $timesheet->delete();
    }
}
