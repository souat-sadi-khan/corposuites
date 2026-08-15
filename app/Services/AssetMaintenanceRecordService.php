<?php

namespace App\Services;

use App\Models\AssetMaintenanceRecord;
use App\Models\AssetMaintenanceSchedule;

class AssetMaintenanceRecordService
{
    protected $scheduleService;

    public function __construct(AssetMaintenanceScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function create(array $data): AssetMaintenanceRecord
    {
        $record = AssetMaintenanceRecord::create($data);

        $this->syncSchedule($record->asset_maintenance_schedule_id);

        return $record;
    }

    public function update(AssetMaintenanceRecord $record, array $data): AssetMaintenanceRecord
    {
        $previousScheduleId = $record->asset_maintenance_schedule_id;

        $record->update($data);
        $updated = $record->fresh();

        // If the record was moved to a different schedule, the one it left
        // has to be recalculated as well as the one it joined.
        if ((int) $previousScheduleId !== (int) $updated->asset_maintenance_schedule_id) {
            $this->syncSchedule($previousScheduleId);
        }

        $this->syncSchedule($updated->asset_maintenance_schedule_id);

        return $updated;
    }

    public function delete(AssetMaintenanceRecord $record): bool
    {
        $scheduleId = $record->asset_maintenance_schedule_id;

        $deleted = $record->delete();

        $this->syncSchedule($scheduleId);

        return $deleted;
    }

    /**
     * Push a schedule's `last_performed_date` to the most recent completed
     * job recorded against it, then let the schedule service re-derive the
     * next due date from it — closing the loop Maintenance Schedule was
     * built to expect.
     *
     * It is recalculated from scratch on every write rather than simply
     * stamped forward, so editing a record's date, cancelling it, moving it
     * to another schedule or deleting it all correct the schedule instead of
     * leaving it stuck at a date no surviving record supports. When no
     * completed job remains, `last_performed_date` goes back to null and the
     * schedule falls due on its start date again.
     */
    protected function syncSchedule(?int $scheduleId): void
    {
        if (! $scheduleId) {
            return;
        }

        $schedule = AssetMaintenanceSchedule::find($scheduleId);

        if (! $schedule) {
            return;
        }

        $lastPerformed = AssetMaintenanceRecord::where('asset_maintenance_schedule_id', $scheduleId)
            ->where('record_status', 'completed')
            ->orderBy('performed_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->value('performed_date');

        $lastPerformed = $lastPerformed ? $lastPerformed->toDateString() : null;

        $schedule->update([
            'last_performed_date' => $lastPerformed,
            'next_due_date' => $this->scheduleService->calculateNextDueDate([
                'frequency' => $schedule->frequency,
                'start_date' => $schedule->start_date?->toDateString(),
                'last_performed_date' => $lastPerformed,
            ]),
        ]);
    }
}
