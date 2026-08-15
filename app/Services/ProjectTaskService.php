<?php

namespace App\Services;

use App\Models\ProjectTask;

class ProjectTaskService
{
    public function create(array $data): ProjectTask
    {
        $data['task_code'] = $this->generateTaskCode();
        $data['sort_order'] = $data['sort_order'] ?? $this->nextSortOrder((int) $data['project_id']);

        return ProjectTask::create($this->withDerivedFields($data));
    }

    public function update(ProjectTask $task, array $data): ProjectTask
    {
        // Issued once — Time Tracking, Timesheets and Task Dependencies will
        // all reference it, so it must never be re-issued.
        unset($data['task_code']);

        $task->update($this->withDerivedFields($data, $task));

        return $task->fresh();
    }

    public function delete(ProjectTask $task): bool
    {
        return $task->delete();
    }

    /**
     * Move a task to another board column and renumber that column.
     *
     * Added for the Task Board Kanban screen: a drag-and-drop only ever
     * changes the state and the position, so it routes through here rather
     * than the full update path (same reasoning as
     * OpportunityService::updateStage). The Done handling still goes through
     * withDerivedFields(), so dropping a card into Done stamps its completion
     * date exactly as the form would.
     *
     * @param  array<int, int|string>  $orderedIds  the column's ids, top to bottom
     */
    public function moveToStatus(ProjectTask $task, string $status, array $orderedIds = []): ProjectTask
    {
        $task->update($this->withDerivedFields(['task_status' => $status], $task));

        foreach (array_values($orderedIds) as $index => $id) {
            ProjectTask::where('id', $id)
                ->where('project_id', $task->project_id)
                ->update(['sort_order' => $index + 1]);
        }

        return $task->fresh();
    }

    /**
     * A done task is 100% complete and carries a completion date (today if
     * none was given). Reopening it clears that date again, so a task can
     * never claim it was finished on a day it was not. Same shape as
     * ProjectService's and ProjectMilestoneService's own handling.
     */
    protected function withDerivedFields(array $data, ?ProjectTask $task = null): array
    {
        $status = $data['task_status'] ?? $task?->task_status;

        if ($status === 'done') {
            $data['progress_percent'] = 100;
            $data['completed_date'] = $data['completed_date']
                ?? $task?->completed_date?->toDateString()
                ?? now()->toDateString();
        } elseif ($task && $task->task_status === 'done') {
            $data['completed_date'] = null;
        }

        return $data;
    }

    /**
     * Tasks are ordered within their own project, so a new one lands at the
     * end of that project's list rather than following a global count.
     */
    protected function nextSortOrder(int $projectId): int
    {
        return (int) ProjectTask::where('project_id', $projectId)->max('sort_order') + 1;
    }

    protected function generateTaskCode(): string
    {
        $lastId = ProjectTask::max('id') ?? 0;

        return 'TSK-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
