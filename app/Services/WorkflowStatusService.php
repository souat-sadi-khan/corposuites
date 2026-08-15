<?php

namespace App\Services;

use App\Models\WorkflowStatus;

class WorkflowStatusService
{
    public function create(array $data): WorkflowStatus
    {
        return WorkflowStatus::create($data);
    }

    public function update(WorkflowStatus $workflowStatus, array $data): WorkflowStatus
    {
        $workflowStatus->update($data);
        return $workflowStatus;
    }

    public function delete(WorkflowStatus $workflowStatus): bool
    {
        return $workflowStatus->delete();
    }
}
