<?php

namespace App\Services;

use App\Models\DepartmentBudget;

class DepartmentBudgetService
{
    public function create(array $data): DepartmentBudget
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['budget_code'] = $this->generateBudgetCode();
        $data['version'] = $this->nextVersion((int) $data['department_id'], $data['period_start'], $data['period_end']);
        $data = $this->withDerivedFields($data, $items);

        $budget = DepartmentBudget::create($data);
        $this->syncItems($budget, $items);

        return $budget;
    }

    public function update(DepartmentBudget $budget, array $data): DepartmentBudget
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Issued once, and the version is owned by the service — none of
        // these are re-derived on edit. department_id/period_start/
        // period_end are all locked (the form does not allow changing
        // them): the version was numbered against this exact department +
        // period combination, so moving any of them behind the user's back
        // would silently renumber it against a different sequence — the
        // same reasoning ProjectBudgetService locks project_id for, and
        // BudgetService locks the period for.
        unset($data['budget_code'], $data['version'], $data['department_id'], $data['period_start'], $data['period_end']);

        $data = $this->withDerivedFields($data, $items, $budget);

        $budget->update($data);
        $this->syncItems($budget, $items);

        return $budget->fresh();
    }

    public function delete(DepartmentBudget $budget): bool
    {
        return $budget->delete();
    }

    /**
     * The header total is always the sum of the lines submitted with it, and
     * an approved budget carries an approval date (today if none was given).
     * Moving a budget back off "approved" clears that date, so it can never
     * claim it was signed off on a day it was not. Same shape
     * BudgetService/ProjectBudgetService already established.
     */
    protected function withDerivedFields(array $data, array $items, ?DepartmentBudget $budget = null): array
    {
        $data['total_amount'] = collect($items)->sum(fn ($item) => (float) ($item['planned_amount'] ?? 0));

        $status = $data['budget_status'] ?? $budget?->budget_status;

        if ($status === 'approved') {
            $data['approved_date'] = $data['approved_date']
                ?? $budget?->approved_date?->toDateString()
                ?? now()->toDateString();
        } elseif ($budget && $budget->budget_status === 'approved') {
            $data['approved_date'] = null;
            $data['approved_by'] = null;
        }

        return $data;
    }

    protected function syncItems(DepartmentBudget $budget, array $items): void
    {
        $budget->items()->delete();

        foreach ($items as $item) {
            $budget->items()->create([
                'chart_of_account_id' => $item['chart_of_account_id'],
                'planned_amount' => $item['planned_amount'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    /**
     * Budgets are versioned per department + period (original, then
     * revisions), so the next version follows that exact combination's own
     * highest — not a global counter, and not shared across departments.
     */
    protected function nextVersion(int $departmentId, string $periodStart, string $periodEnd): int
    {
        return (int) DepartmentBudget::where('department_id', $departmentId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->max('version') + 1;
    }

    protected function generateBudgetCode(): string
    {
        $lastId = DepartmentBudget::max('id') ?? 0;

        return 'DBG-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
