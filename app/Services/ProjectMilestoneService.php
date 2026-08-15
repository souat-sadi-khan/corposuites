<?php

namespace App\Services;

use App\Models\ProjectMilestone;

class ProjectMilestoneService
{
    public function create(array $data): ProjectMilestone
    {
        $data['sort_order'] = $data['sort_order'] ?? $this->nextSortOrder((int) $data['project_id']);

        return ProjectMilestone::create($this->withDerivedFields($data));
    }

    public function update(ProjectMilestone $milestone, array $data): ProjectMilestone
    {
        $milestone->update($this->withDerivedFields($data, $milestone));

        return $milestone->fresh();
    }

    public function delete(ProjectMilestone $milestone): bool
    {
        return $milestone->delete();
    }

    /**
     * A completed milestone carries a completion date — today if none was
     * given. Moving it back off "completed" clears that date again, so it can
     * never claim it was delivered on a day it was not. Same shape as
     * ProjectService's own completion handling.
     */
    protected function withDerivedFields(array $data, ?ProjectMilestone $milestone = null): array
    {
        $status = $data['milestone_status'] ?? $milestone?->milestone_status;

        if ($status === 'completed') {
            $data['completed_date'] = $data['completed_date']
                ?? $milestone?->completed_date?->toDateString()
                ?? now()->toDateString();
        } elseif ($milestone && $milestone->milestone_status === 'completed') {
            $data['completed_date'] = null;
        }

        return $data;
    }

    /**
     * Milestones are sequenced within their own project, so a new one lands
     * at the end of that project's list rather than following a global count.
     */
    protected function nextSortOrder(int $projectId): int
    {
        return (int) ProjectMilestone::where('project_id', $projectId)->max('sort_order') + 1;
    }
}
