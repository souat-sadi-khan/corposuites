<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStepApprover;
use App\Traits\ActivityLogger;

class WorkflowNotifier
{
    use ActivityLogger;

    public function __construct(protected ApprovalDelegationService $delegations)
    {
    }

    /**
     * Fire all active notification triggers configured for the instance's
     * workflow definition that match the given event
     * ('step_pending'|'approved'|'rejected'|'resubmitted'|'completed').
     */
    public function notify(WorkflowInstance $instance, string $event): void
    {
        $triggers = $instance->workflowDefinition
            ->notificationTriggers()
            ->where('event', $event)
            ->where('status', true)
            ->get();

        foreach ($triggers as $trigger) {
            foreach ($this->resolveRecipients($instance, $trigger) as $adminId) {
                $this->notifyAdmin($instance, $trigger, $event, $adminId);
            }
        }
    }

    /**
     * @return int[] Admin IDs to notify.
     */
    protected function resolveRecipients(WorkflowInstance $instance, $trigger): array
    {
        switch ($trigger->notify_type) {
            case 'initiator':
                return $instance->initiated_by ? [$instance->initiated_by] : [];

            case 'approver':
                if (!$instance->current_step_id) {
                    return [];
                }

                $approverIds = WorkflowStepApprover::where('workflow_step_id', $instance->current_step_id)
                    ->get()
                    ->flatMap(fn ($approver) => $this->resolveStepApproverAdminIds($approver))
                    ->unique()
                    ->values()
                    ->all();

                // Route around any approver who is away by substituting their
                // active delegate for today (Phase E3).
                return $this->delegations->mapApprovers($approverIds);

            case 'user':
                return $trigger->notify_id ? [$trigger->notify_id] : [];

            case 'role':
                if (!$trigger->notify_id) {
                    return [];
                }

                return Admin::role($trigger->notify_id)->pluck('id')->all();

            default:
                return [];
        }
    }

    protected function resolveStepApproverAdminIds(WorkflowStepApprover $approver): array
    {
        if (!$approver->approver_id) {
            return [];
        }

        switch ($approver->approver_type) {
            case 'user':
                return [$approver->approver_id];

            case 'role':
                return Admin::role($approver->approver_id)->pluck('id')->all();

            case 'designation':
                return Admin::whereHas('employee', function ($q) use ($approver) {
                    $q->where('designation_id', $approver->approver_id);
                })->pluck('id')->all();

            default:
                return [];
        }
    }

    protected function notifyAdmin(WorkflowInstance $instance, $trigger, string $event, int $adminId): void
    {
        $title = 'Workflow ' . str_replace('_', ' ', $event);

        $message = $trigger->template_message
            ? str_replace(
                ['{module}', '{instance_id}', '{status}', '{event}'],
                [$instance->workflowDefinition->module_key, $instance->id, $instance->current_status, $event],
                $trigger->template_message
            )
            : $title . ' for ' . $instance->workflowDefinition->module_key . ' #' . $instance->approvable_id;

        $this->logActivity([
            'actor_type' => 'system',
            'actor_id' => null,
            'module' => 'Workflow',
            'action' => $event,
            'model' => 'WorkflowInstance',
            'model_id' => $instance->id,
            'description' => $message,
        ]);

        $activityLog = \App\Models\ActivityLog::latest('id')->first();

        Notification::create([
            'activity_log_id' => $activityLog?->id,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
