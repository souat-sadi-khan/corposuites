<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepApprover extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_step_id', 'approver_type', 'approver_id'
    ];

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    /**
     * A human-readable label for this configured approver slot — e.g.
     * "Jane Doe" for a specific user, "Any HR Manager (role)" for a role,
     * "Any Finance designation" for a designation — used by the Leave
     * Request detail modal's workflow stepper (and reusable anywhere else
     * a workflow step's approver list needs to be displayed) so this
     * approver_type → readable-name resolution lives in exactly one place,
     * mirroring the same approver_type switch WorkflowNotifier already uses
     * to resolve actual notification recipients.
     */
    public function getApproverLabelAttribute(): string
    {
        if (!$this->approver_id) {
            return ucfirst($this->approver_type ?? 'Unknown');
        }

        return match ($this->approver_type) {
            'user' => Admin::find($this->approver_id)?->name ?? 'Unknown user',
            'role' => (\Spatie\Permission\Models\Role::find($this->approver_id)?->name ?? 'Unknown role') . ' (any member)',
            'designation' => (Designation::find($this->approver_id)?->name ?? 'Unknown designation') . ' (any member)',
            default => 'Unknown approver',
        };
    }
}
