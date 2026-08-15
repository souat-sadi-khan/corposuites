<?php

namespace App\Services;

use App\Http\Requests\Admin\WorkflowDefinitionRequest;
use App\Models\WorkflowDefinition;
use Illuminate\Support\Facades\DB;

class WorkflowDefinitionService
{
    /**
     * module_key => approvable model FQCN. Mirrors
     * App\Http\Requests\Admin\WorkflowDefinitionRequest::MODULE_MAP.
     */
    public const MODULE_MAP = WorkflowDefinitionRequest::MODULE_MAP;

    public function create(array $data): WorkflowDefinition
    {
        return DB::transaction(function () use ($data) {
            $steps = $data['steps'] ?? [];
            unset($data['steps']);

            $data['approvable_type'] = self::MODULE_MAP[$data['module_key']];
            $data['created_by'] = auth()->guard('admin')->id();

            $workflowDefinition = WorkflowDefinition::create($data);

            $this->syncSteps($workflowDefinition, $steps);

            return $workflowDefinition;
        });
    }

    public function update(WorkflowDefinition $workflowDefinition, array $data): WorkflowDefinition
    {
        return DB::transaction(function () use ($workflowDefinition, $data) {
            $steps = $data['steps'] ?? [];
            unset($data['steps']);

            $data['approvable_type'] = self::MODULE_MAP[$data['module_key']];

            $workflowDefinition->update($data);

            // Replace all steps/approvers — cascade deletes approvers via FK.
            $workflowDefinition->steps()->delete();
            $this->syncSteps($workflowDefinition, $steps);

            return $workflowDefinition;
        });
    }

    public function delete(WorkflowDefinition $workflowDefinition): bool
    {
        return $workflowDefinition->delete();
    }

    public function toggleStatus(WorkflowDefinition $workflowDefinition): WorkflowDefinition
    {
        $workflowDefinition->status = ! $workflowDefinition->status;
        $workflowDefinition->save();

        return $workflowDefinition;
    }

    protected function syncSteps(WorkflowDefinition $workflowDefinition, array $steps): void
    {
        foreach ($steps as $index => $step) {
            $workflowStep = $workflowDefinition->steps()->create([
                'step_order' => $index + 1,
                'name' => $step['name'],
                'approval_type' => $step['approval_type'],
            ]);

            foreach ($step['approvers'] ?? [] as $approver) {
                $workflowStep->approvers()->create([
                    'approver_type' => $approver['approver_type'],
                    'approver_id' => $approver['approver_id'],
                ]);
            }
        }
    }
}
