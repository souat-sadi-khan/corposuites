<?php

namespace App\Services;

use App\Models\ProjectBudget;

class ProjectBudgetService
{
    public function create(array $data): ProjectBudget
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['budget_code'] = $this->generateBudgetCode();
        $data['version'] = $this->nextVersion((int) $data['project_id']);
        $data = $this->withDerivedFields($data, $items);

        $budget = ProjectBudget::create($data);
        $this->syncItems($budget, $items);

        return $budget;
    }

    public function update(ProjectBudget $budget, array $data): ProjectBudget
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Issued once, and the version is owned by the service — neither is
        // re-derived on edit, so moving a budget between projects (which the
        // form does not allow) could never renumber it behind the user's back.
        unset($data['budget_code'], $data['version'], $data['project_id']);

        $data = $this->withDerivedFields($data, $items, $budget);

        $budget->update($data);
        $this->syncItems($budget, $items);

        return $budget->fresh();
    }

    public function delete(ProjectBudget $budget): bool
    {
        return $budget->delete();
    }

    /**
     * The header total is always the sum of the lines submitted with it, and
     * an approved budget carries an approval date (today if none was given).
     * Moving a budget back off "approved" clears that date, so it can never
     * claim it was signed off on a day it was not.
     */
    protected function withDerivedFields(array $data, array $items, ?ProjectBudget $budget = null): array
    {
        $data['total_amount'] = collect($items)->sum(fn ($item) => (float) ($item['amount'] ?? 0));

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

    protected function syncItems(ProjectBudget $budget, array $items): void
    {
        $budget->items()->delete();

        foreach ($items as $item) {
            $budget->items()->create([
                'category' => $item['category'],
                'description' => $item['description'] ?? null,
                'amount' => $item['amount'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    /**
     * Budgets are versioned per project (original, then revisions), so the
     * next version follows that project's own highest — not a global counter.
     */
    protected function nextVersion(int $projectId): int
    {
        return (int) ProjectBudget::where('project_id', $projectId)->max('version') + 1;
    }

    protected function generateBudgetCode(): string
    {
        $lastId = ProjectBudget::max('id') ?? 0;

        return 'PBG-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
