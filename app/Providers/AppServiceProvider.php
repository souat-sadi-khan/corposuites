<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use App\Models\Lang\Language;
use App\Events\WorkflowApproved;
use App\Events\WorkflowRejected;
use App\Events\WorkflowResubmitted;
use App\Listeners\SyncLeaveRequestApproval;
use App\Listeners\SyncExpenseClaimApproval;
use App\Listeners\SyncAttendanceAdjustmentApproval;
use App\Listeners\SyncEmployeeLoanApproval;
use App\Listeners\SyncPurchaseRequestApproval;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerWorkflowListeners();

        View::composer('admin.layout.partials.header', function ($view) {
            $view->with(
                'languages',
                Language::where('is_active', 1)
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->get()
            );
        });
    }

    /**
     * Register Workflow Engine listeners explicitly (this app has no
     * EventServiceProvider / auto-discovery configured in bootstrap/app.php,
     * so listeners must be wired here to actually fire).
     */
    protected function registerWorkflowListeners(): void
    {
        Event::listen(WorkflowApproved::class, [SyncLeaveRequestApproval::class, 'handleApproved']);
        Event::listen(WorkflowRejected::class, [SyncLeaveRequestApproval::class, 'handleRejected']);
        Event::listen(WorkflowResubmitted::class, [SyncLeaveRequestApproval::class, 'handleResubmitted']);

        Event::listen(WorkflowApproved::class, [SyncExpenseClaimApproval::class, 'handleApproved']);
        Event::listen(WorkflowRejected::class, [SyncExpenseClaimApproval::class, 'handleRejected']);
        Event::listen(WorkflowResubmitted::class, [SyncExpenseClaimApproval::class, 'handleResubmitted']);

        Event::listen(WorkflowApproved::class, [SyncAttendanceAdjustmentApproval::class, 'handleApproved']);
        Event::listen(WorkflowRejected::class, [SyncAttendanceAdjustmentApproval::class, 'handleRejected']);
        Event::listen(WorkflowResubmitted::class, [SyncAttendanceAdjustmentApproval::class, 'handleResubmitted']);

        Event::listen(WorkflowApproved::class, [SyncEmployeeLoanApproval::class, 'handleApproved']);
        Event::listen(WorkflowRejected::class, [SyncEmployeeLoanApproval::class, 'handleRejected']);
        Event::listen(WorkflowResubmitted::class, [SyncEmployeeLoanApproval::class, 'handleResubmitted']);

        Event::listen(WorkflowApproved::class, [SyncPurchaseRequestApproval::class, 'handleApproved']);
        Event::listen(WorkflowRejected::class, [SyncPurchaseRequestApproval::class, 'handleRejected']);
        Event::listen(WorkflowResubmitted::class, [SyncPurchaseRequestApproval::class, 'handleResubmitted']);
    }
}
