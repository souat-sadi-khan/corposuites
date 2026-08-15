<?php

namespace App\Services;

use App\Models\WorkflowNotificationTrigger;

class WorkflowNotificationTriggerService
{
    public function create(array $data): WorkflowNotificationTrigger
    {
        return WorkflowNotificationTrigger::create($data);
    }

    public function update(WorkflowNotificationTrigger $workflowNotificationTrigger, array $data): WorkflowNotificationTrigger
    {
        $workflowNotificationTrigger->update($data);
        return $workflowNotificationTrigger;
    }

    public function delete(WorkflowNotificationTrigger $workflowNotificationTrigger): bool
    {
        return $workflowNotificationTrigger->delete();
    }

    public function updateStatus(WorkflowNotificationTrigger $workflowNotificationTrigger, bool $status): WorkflowNotificationTrigger
    {
        $workflowNotificationTrigger->status = $status;
        $workflowNotificationTrigger->save();
        return $workflowNotificationTrigger;
    }
}
