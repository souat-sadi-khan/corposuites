<?php

namespace App\Services;

use App\Models\WorkflowTemplate;

class WorkflowTemplateService
{
    public function create(array $data): WorkflowTemplate
    {
        return WorkflowTemplate::create($data);
    }

    public function update(WorkflowTemplate $workflowTemplate, array $data): WorkflowTemplate
    {
        $workflowTemplate->update($data);
        return $workflowTemplate;
    }

    public function delete(WorkflowTemplate $workflowTemplate): bool
    {
        return $workflowTemplate->delete();
    }
}
