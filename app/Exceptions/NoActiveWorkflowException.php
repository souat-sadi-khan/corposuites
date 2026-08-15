<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by WorkflowEngineService::start() when no active WorkflowDefinition
 * exists for the approvable model's class. No custom Exceptions dir/convention
 * existed in this codebase prior to this task, so this follows plain Laravel
 * exception conventions (extends RuntimeException, no custom render/report).
 */
class NoActiveWorkflowException extends RuntimeException
{
    public static function forClass(string $approvableType): self
    {
        return new self("No active workflow definition found for [{$approvableType}].");
    }
}
