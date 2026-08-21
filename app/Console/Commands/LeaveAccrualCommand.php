<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LeaveAccrualCommand extends Command
{
    protected $signature = 'leave:accrue {--month= : Month (1-12) to accrue for; defaults to current}
                                          {--year= : Year to accrue for; defaults to current}';

    protected $description = 'Run monthly leave accrual for all eligible employees (monthly-accrual leave types).';

    public function handle(LeaveAccrualService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $month = (int) ($this->option('month') ?: now()->month);
        $asOf = Carbon::create($year, $month, 1)->endOfMonth();

        $touched = $service->runMonthlyAccrual($asOf);

        $this->info("Leave accrual complete for {$asOf->format('F Y')}: {$touched} balance(s) updated.");

        return self::SUCCESS;
    }
}
