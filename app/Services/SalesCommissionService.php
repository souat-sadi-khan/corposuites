<?php

namespace App\Services;

use App\Models\SalesCommission;
use App\Models\SalesOrder;

class SalesCommissionService
{
    public function create(array $data): SalesCommission
    {
        $data = $this->calculateAmounts($data);

        return SalesCommission::create($data);
    }

    public function update(SalesCommission $salesCommission, array $data): SalesCommission
    {
        $data = $this->calculateAmounts($data);

        $salesCommission->update($data);

        return $salesCommission;
    }

    public function delete(SalesCommission $salesCommission): bool
    {
        return $salesCommission->delete();
    }

    public function markAsPaid(SalesCommission $salesCommission): SalesCommission
    {
        $salesCommission->update([
            'payment_status' => 'paid',
            'payment_date' => now()->toDateString(),
        ]);

        return $salesCommission;
    }

    /**
     * Snapshot the salesperson's non-cancelled Sales Order total for the period,
     * then compute commission_amount from it. Stored, not recomputed later —
     * once a commission is calculated (and especially once paid), it must not
     * silently drift if orders are edited/cancelled afterward.
     */
    protected function calculateAmounts(array $data): array
    {
        $salesAmount = (float) SalesOrder::where('assigned_to', $data['admin_id'])
            ->where('order_status', '!=', 'cancelled')
            ->whereBetween('order_date', [$data['period_start'], $data['period_end']])
            ->sum('grand_total');

        $rate = (float) $data['commission_rate'];

        $data['sales_amount'] = $salesAmount;
        $data['commission_amount'] = round($salesAmount * ($rate / 100), 2);

        return $data;
    }
}
