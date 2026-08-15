<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use Illuminate\Foundation\Events\Dispatchable;

class WorkflowRejected
{
    use Dispatchable;

    public function __construct(public readonly WorkflowInstance $instance)
    {
    }
}
