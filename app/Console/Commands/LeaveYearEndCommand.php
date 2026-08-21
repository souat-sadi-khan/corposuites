<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

class LeaveYearEndCommand extends Command
{
    protected $signature = 'leave:year-end {--from= : The year being closed; defaults to the previous year}';

    protected $description = 'Close a leave year: carry forward remaining balances (within cap) into the next year.';

    public function handle(LeaveAccrualService $service): int
    {
        $fromYear = (int) ($this->option('from') ?: (now()->year - 1));

        $processed = $service->runYearEndCarryForward($fromYear);

        $this->info("Year-end close for {$fromYear} complete: {$processed} balance(s) rolled into " . ($fromYear + 1) . '.');

        return self::SUCCESS;
    }
}
