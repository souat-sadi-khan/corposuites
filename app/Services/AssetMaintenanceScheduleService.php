<?php

namespace App\Services;

use App\Models\AssetMaintenanceSchedule;
use Illuminate\Support\Carbon;

class AssetMaintenanceScheduleService
{
    public function create(array $data): AssetMaintenanceSchedule
    {
        $data['next_due_date'] = $this->calculateNextDueDate($data);

        return AssetMaintenanceSchedule::create($data);
    }

    public function update(AssetMaintenanceSchedule $schedule, array $data): AssetMaintenanceSchedule
    {
        $data['next_due_date'] = $this->calculateNextDueDate($data);

        $schedule->update($data);

        return $schedule->fresh();
    }

    public function delete(AssetMaintenanceSchedule $schedule): bool
    {
        return $schedule->delete();
    }

    /**
     * The next due date is always derived, never taken from the form — a
     * user-entered value could silently contradict the frequency, and the
     * two would then drift apart on every edit. It counts forward from the
     * last time the job was actually performed, falling back to the start
     * date when it never has been.
     *
     * A one-time schedule is due on its start date and never again; once
     * performed it has no further due date at all.
     */
    public function calculateNextDueDate(array $data): ?string
    {
        $frequency = $data['frequency'] ?? 'monthly';
        $startDate = $data['start_date'] ?? null;
        $lastPerformed = $data['last_performed_date'] ?? null;

        if (! $startDate) {
            return null;
        }

        if ($frequency === 'one_time') {
            return $lastPerformed ? null : Carbon::parse($startDate)->toDateString();
        }

        $anchor = Carbon::parse($lastPerformed ?: $startDate);

        // Never performed yet: the start date is itself the first due date.
        if (! $lastPerformed) {
            return $anchor->toDateString();
        }

        return match ($frequency) {
            'weekly' => $anchor->copy()->addWeek()->toDateString(),
            'monthly' => $anchor->copy()->addMonth()->toDateString(),
            'quarterly' => $anchor->copy()->addMonths(3)->toDateString(),
            'half_yearly' => $anchor->copy()->addMonths(6)->toDateString(),
            'yearly' => $anchor->copy()->addYear()->toDateString(),
            default => $anchor->toDateString(),
        };
    }
}
