<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

class LeaveExpireCarryForwardCommand extends Command
{
    protected $signature = 'leave:expire-carry-forward';

    protected $description = 'Forfeit unused carried-forward leave days whose expiry date has passed.';

    public function handle(LeaveAccrualService $service): int
    {
        $adjusted = $service->expireCarryForward();

        $this->info("Carry-forward expiry complete: {$adjusted} balance(s) adjusted.");

        return self::SUCCESS;
    }
}
