<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveRequestService
{
    public function create(array $data): LeaveRequest
    {
        $data['total_days'] = $this->resolveTotalDays($data);

        return LeaveRequest::create($data);
    }

    public function update(LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        $wasApproved = $leaveRequest->approval_status === 'approved';
        $originalDays = (float) $leaveRequest->total_days;
        $originalEmployeeId = $leaveRequest->employee_id;
        $originalTypeId = $leaveRequest->leave_type_id;
        $originalYear = Carbon::parse($leaveRequest->start_date)->year;

        $data['total_days'] = $this->resolveTotalDays($data);

        // If the request was already approved, reverse the original deduction from the
        // original balance bucket before applying the edit, so a changed date range,
        // leave type, or year re-syncs the balance correctly.
        if ($wasApproved) {
            $this->adjustBalanceFor($originalEmployeeId, $originalTypeId, $originalYear, -$originalDays);
        }

        $leaveRequest->update($data);

        // Re-apply the new deduction against the (possibly new) balance bucket.
        if ($wasApproved) {
            $this->adjustBalance($leaveRequest, (float) $leaveRequest->total_days);
        }

        return $leaveRequest;
    }

    /**
     * Resolve the billable leave duration for a submission. A half-day request
     * always counts as 0.5 of a single working day; otherwise the working-day
     * count across the range is used.
     */
    protected function resolveTotalDays(array $data): float
    {
        if (($data['duration_type'] ?? 'full_day') === 'half_day') {
            // A half day is confined to a single date; force end = start.
            return 0.5;
        }

        return $this->calculateDays($data['start_date'], $data['end_date']);
    }

    public function delete(LeaveRequest $leaveRequest): bool
    {
        if ($leaveRequest->approval_status === 'approved') {
            $this->adjustBalance($leaveRequest, -$leaveRequest->total_days);
        }

        return $leaveRequest->delete();
    }

    public function approve(LeaveRequest $leaveRequest, bool $override = false): LeaveRequest
    {
        if ($leaveRequest->approval_status !== 'pending') {
            throw new \RuntimeException('Only pending leave requests can be approved.');
        }

        if (!$override && !$this->hasSufficientBalance($leaveRequest)) {
            throw new \RuntimeException('Insufficient leave balance to approve this request.');
        }

        $leaveRequest->update(['approval_status' => 'approved']);
        $this->adjustBalance($leaveRequest, $leaveRequest->total_days);

        return $leaveRequest;
    }

    public function reject(LeaveRequest $leaveRequest): LeaveRequest
    {
        if ($leaveRequest->approval_status !== 'pending') {
            throw new \RuntimeException('Only pending leave requests can be rejected.');
        }

        $leaveRequest->update(['approval_status' => 'rejected']);
        return $leaveRequest;
    }

    /**
     * Cancel a request. If it was already approved, the deducted balance is
     * refunded. Pending requests are simply marked cancelled.
     */
    public function cancel(LeaveRequest $leaveRequest, ?string $reason = null): LeaveRequest
    {
        if ($leaveRequest->approval_status !== 'pending' && $leaveRequest->approval_status !== 'approved') {
            throw new \RuntimeException('Only pending or approved leave requests can be cancelled.');
        }
        if ($leaveRequest->approval_status === 'approved') {
            $this->adjustBalance($leaveRequest, -$leaveRequest->total_days);
        }

        $leaveRequest->update([
            'approval_status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        return $leaveRequest;
    }

    /**
     * Remaining balance for the bucket this request would deduct from
     * (employee + leave type + the year of the request's start date).
     */
    public function remainingBalance(LeaveRequest $leaveRequest): float
    {
        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', Carbon::parse($leaveRequest->start_date)->year)
            ->first();

        if (!$balance) {
            return 0.0;
        }

        return round((float) $balance->allocated_days - (float) $balance->used_days, 2);
    }

    /**
     * Whether the employee has enough remaining balance to cover this request.
     */
    public function hasSufficientBalance(LeaveRequest $leaveRequest): bool
    {
        return $this->remainingBalance($leaveRequest) >= (float) $leaveRequest->total_days;
    }

    /**
     * Other pending/approved leave requests for the same employee whose date range
     * overlaps this one. Used to warn (not block) on submit/edit.
     */
    public function overlappingRequests(LeaveRequest $leaveRequest): Collection
    {
        return LeaveRequest::where('employee_id', $leaveRequest->employee_id)
            ->where('id', '!=', $leaveRequest->id)
            ->whereIn('approval_status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $leaveRequest->end_date)
            ->whereDate('end_date', '>=', $leaveRequest->start_date)
            ->get();
    }

    /**
     * Public accessor for the working-day count between two dates
     * (weekends + active holidays excluded). Reused by policy validation.
     */
    public function workingDays($startDate, $endDate): float
    {
        return $this->calculateDays($startDate, $endDate);
    }

    /**
     * Count leave days between two dates, excluding weekends and active holidays.
     * Weekend days are configurable via the `leave_weekend_days` system setting
     * (comma-separated Carbon day numbers: 0=Sun … 6=Sat); default is Fri+Sat (5,6).
     */
    protected function calculateDays($startDate, $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 0.0;
        }

        $weekendDays = $this->weekendDays();
        $holidays = $this->holidayDates($start, $end);

        $days = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->dayOfWeek, $weekendDays, true)) {
                continue;
            }

            if (in_array($date->toDateString(), $holidays, true)) {
                continue;
            }

            $days++;
        }

        return (float) $days;
    }

    /**
     * Configured weekend day numbers (Carbon: 0=Sunday … 6=Saturday).
     */
    protected function weekendDays(): array
    {
        $raw = get_settings('leave_weekend_days', '5,6');

        return collect(explode(',', (string) $raw))
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d >= 0 && $d <= 6)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Active holiday dates (Y-m-d strings) within the given range.
     */
    protected function holidayDates(Carbon $start, Carbon $end): array
    {
        return Holiday::where('status', 1)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
    }

    protected function adjustBalance(LeaveRequest $leaveRequest, float $days): void
    {
        $this->adjustBalanceFor(
            $leaveRequest->employee_id,
            $leaveRequest->leave_type_id,
            Carbon::parse($leaveRequest->start_date)->year,
            $days
        );
    }

    protected function adjustBalanceFor(int $employeeId, int $leaveTypeId, int $year, float $days): void
    {
        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            ['allocated_days' => 0, 'used_days' => 0]
        );

        $balance->update(['used_days' => max(0, $balance->used_days + $days)]);
    }
}
