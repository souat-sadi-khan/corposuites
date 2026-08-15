<?php

namespace App\Services;

use App\Events\WorkflowApproved;
use App\Events\WorkflowRejected;
use App\Events\WorkflowResubmitted;
use App\Events\WorkflowStepAdvanced;
use App\Exceptions\NoActiveWorkflowException;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowInstanceApproval;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class WorkflowEngineService
{
    public function __construct(protected WorkflowNotifier $notifier)
    {
    }

    /**
     * Resolve the active WorkflowDefinition configured for a given approvable model class.
     */
    public function activeDefinitionFor(string $approvableType): ?WorkflowDefinition
    {
        return WorkflowDefinition::active()
            ->where('approvable_type', $approvableType)
            ->first();
    }

    /**
     * Start a new workflow instance for an approvable model.
     */
    public function start(Model $approvable, ?int $initiatedByAdminId = null): WorkflowInstance
    {
        $definition = $this->activeDefinitionFor($approvable::class);

        if (!$definition) {
            throw NoActiveWorkflowException::forClass($approvable::class);
        }

        $firstStep = $definition->steps()->orderBy('step_order')->first();

        if (!$firstStep) {
            throw new RuntimeException(
                'Workflow definition #' . $definition->id . ' has no steps configured.'
            );
        }

        $instance = WorkflowInstance::create([
            'workflow_definition_id' => $definition->id,
            'approvable_type' => $approvable::class,
            'approvable_id' => $approvable->getKey(),
            'current_step_id' => $firstStep->id,
            'current_status' => 'pending',
            'initiated_by' => $initiatedByAdminId,
            'resubmission_of' => null,
            'resubmission_count' => 0,
        ]);

        $this->notifier->notify($instance, 'step_pending');

        return $instance;
    }

    /**
     * Record an approver action against a workflow instance and advance/complete/reject
     * the instance as required by the current step's approval_type.
     */
    public function act(WorkflowInstance $instance, int $approverAdminId, string $action, ?string $remarks = null): WorkflowInstance
    {
        if (!in_array($action, ['approved', 'rejected', 'on_hold', 'commented'], true)) {
            throw new RuntimeException('Invalid workflow action: ' . $action);
        }

        $currentStep = $instance->currentStep;

        WorkflowInstanceApproval::create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $currentStep?->id,
            'approver_id' => $approverAdminId,
            'action' => $action,
            'remarks' => $remarks,
            'acted_at' => now(),
        ]);

        if ($action === 'rejected') {
            $instance->update([
                'current_status' => 'rejected',
                'completed_at' => now(),
            ]);

            $this->notifier->notify($instance, 'rejected');
            WorkflowRejected::dispatch($instance->fresh());

            return $instance->fresh();
        }

        if ($action === 'approved') {
            return $this->handleApproval($instance, $currentStep);
        }

        // 'on_hold' / 'commented' — logged only, no status change.
        return $instance->fresh();
    }

    protected function handleApproval(WorkflowInstance $instance, ?WorkflowStep $currentStep): WorkflowInstance
    {
        if (!$currentStep) {
            // No step configured (shouldn't happen once builder exists) — complete immediately.
            return $this->completeInstance($instance);
        }

        $approvalType = $currentStep->approval_type;

        if ($approvalType === 'all_must_approve') {
            $requiredApprovers = $currentStep->approvers()->count();
            $approvedCount = WorkflowInstanceApproval::where('workflow_instance_id', $instance->id)
                ->where('workflow_step_id', $currentStep->id)
                ->where('action', 'approved')
                ->count();

            if ($requiredApprovers > 0 && $approvedCount < $requiredApprovers) {
                // Still waiting on other approvers for this step.
                return $instance->fresh();
            }
        }

        // 'single' and 'any_one_approves' complete the step on the first approval,
        // and 'all_must_approve' falls through here once every approver has acted.
        return $this->advanceOrComplete($instance, $currentStep);
    }

    protected function advanceOrComplete(WorkflowInstance $instance, WorkflowStep $currentStep): WorkflowInstance
    {
        $nextStep = WorkflowStep::where('workflow_definition_id', $instance->workflow_definition_id)
            ->where('step_order', '>', $currentStep->step_order)
            ->orderBy('step_order')
            ->first();

        if ($nextStep) {
            $instance->update(['current_step_id' => $nextStep->id]);
            $instance = $instance->fresh();
            $this->notifier->notify($instance, 'step_pending');
            WorkflowStepAdvanced::dispatch($instance);

            return $instance;
        }

        return $this->completeInstance($instance);
    }

    protected function completeInstance(WorkflowInstance $instance): WorkflowInstance
    {
        $instance->update([
            'current_status' => 'approved',
            'completed_at' => now(),
        ]);

        $instance = $instance->fresh();

        $this->notifier->notify($instance, 'approved');
        $this->notifier->notify($instance, 'completed');

        WorkflowApproved::dispatch($instance);

        return $instance;
    }

    /**
     * Resubmit a previously rejected approvable through a fresh workflow instance.
     */
    public function resubmit(Model $approvable, ?int $initiatedByAdminId = null): WorkflowInstance
    {
        $latest = WorkflowInstance::where('approvable_type', $approvable::class)
            ->where('approvable_id', $approvable->getKey())
            ->latest('id')
            ->first();

        if (!$latest || $latest->current_status !== 'rejected') {
            throw new RuntimeException(
                'Cannot resubmit: no rejected workflow instance found for this record.'
            );
        }

        $definition = $this->activeDefinitionFor($approvable::class);

        if (!$definition) {
            throw NoActiveWorkflowException::forClass($approvable::class);
        }

        $firstStep = $definition->steps()->orderBy('step_order')->first();

        if (!$firstStep) {
            throw new RuntimeException(
                'Workflow definition #' . $definition->id . ' has no steps configured.'
            );
        }

        WorkflowInstanceApproval::create([
            'workflow_instance_id' => $latest->id,
            'workflow_step_id' => $latest->current_step_id,
            'approver_id' => $initiatedByAdminId,
            'action' => 'resubmitted',
            'remarks' => null,
            'acted_at' => now(),
        ]);

        $newInstance = WorkflowInstance::create([
            'workflow_definition_id' => $definition->id,
            'approvable_type' => $approvable::class,
            'approvable_id' => $approvable->getKey(),
            'current_step_id' => $firstStep->id,
            'current_status' => 'pending',
            'initiated_by' => $initiatedByAdminId,
            'resubmission_of' => $latest->id,
            'resubmission_count' => $latest->resubmission_count + 1,
        ]);

        $this->notifier->notify($newInstance, 'resubmitted');
        WorkflowResubmitted::dispatch($newInstance);
        $this->notifier->notify($newInstance, 'step_pending');

        return $newInstance;
    }
}
