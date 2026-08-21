<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Phase E1 — activates the dormant Workflow Engine for Leave Requests.
 *
 * Seeds two approval roles (Manager, HR) and an ACTIVE Manager -> HR sequential
 * WorkflowDefinition for App\Models\LeaveRequest, plus notification triggers.
 * Fully idempotent, so it is safe to run on an already-seeded database.
 *
 * NOTE: this creates the roles and the workflow; assigning specific admins to the
 * Manager / HR roles remains an operational step (Roles & Permissions screen),
 * since the seeder cannot know which admins are managers.
 */
class LeaveWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $managerRole = $this->approverRole('Manager');
        $hrRole = $this->approverRole('HR');

        $definition = WorkflowDefinition::updateOrCreate(
            [
                'module_key' => 'leave_request',
                'approvable_type' => LeaveRequest::class,
            ],
            [
                'name' => 'Leave Request Approval',
                'approval_mode' => 'sequential',
                'workflow_template_id' => null,
                'status' => true,
                'created_by' => null,
            ]
        );

        // Rebuild steps/approvers idempotently (approvers cascade-delete with steps).
        $definition->steps()->delete();

        $managerStep = $definition->steps()->create([
            'step_order' => 1,
            'name' => 'Manager Approval',
            'approval_type' => 'single',
        ]);
        $managerStep->approvers()->create([
            'approver_type' => 'role',
            'approver_id' => $managerRole->id,
        ]);

        $hrStep = $definition->steps()->create([
            'step_order' => 2,
            'name' => 'HR Approval',
            'approval_type' => 'single',
        ]);
        $hrStep->approvers()->create([
            'approver_type' => 'role',
            'approver_id' => $hrRole->id,
        ]);

        // Rebuild notification triggers idempotently.
        $definition->notificationTriggers()->delete();

        $triggers = [
            ['event' => 'step_pending', 'notify_type' => 'approver', 'notify_id' => null, 'template_message' => 'A leave request (#{instance_id}) is awaiting your approval.'],
            ['event' => 'approved', 'notify_type' => 'initiator', 'notify_id' => null, 'template_message' => 'Your leave request (#{instance_id}) has been approved.'],
            ['event' => 'rejected', 'notify_type' => 'initiator', 'notify_id' => null, 'template_message' => 'Your leave request (#{instance_id}) has been rejected.'],
            ['event' => 'completed', 'notify_type' => 'initiator', 'notify_id' => null, 'template_message' => 'Your leave request (#{instance_id}) is fully approved.'],
        ];

        foreach ($triggers as $trigger) {
            $definition->notificationTriggers()->create($trigger + ['status' => true]);
        }
    }

    /**
     * Create (or reuse) an admin-guard approval role and grant it the minimum
     * leave permissions needed to view and act on leave-request workflows.
     */
    protected function approverRole(string $name): Role
    {
        $role = Role::firstOrCreate(
            ['name' => $name, 'guard_name' => 'admin']
        );

        $permissionNames = ['leave-request.view', 'leave-request.approve', 'leave-balance.view'];

        $permissions = Permission::where('guard_name', 'admin')
            ->whereIn('name', $permissionNames)
            ->get();

        $role->givePermissionTo($permissions);

        return $role;
    }
}
