<?php

namespace App\Contracts;

interface Approvable
{
    /**
     * Key display fields shown to an approver / used in notification templates.
     */
    public function approvalPayload(): array;

    /**
     * Unique key identifying this module to the Workflow Engine
     * (matches WorkflowDefinition::module_key for this approvable_type).
     */
    public function workflowModuleKey(): string;
}
