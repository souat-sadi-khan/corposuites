<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase C — Leave accrual automation.
// Accrue monthly-accrual leave types on the 1st of each month.
Schedule::command('leave:accrue')->monthlyOn(1, '00:30');

// Forfeit expired carry-forward days daily.
Schedule::command('leave:expire-carry-forward')->dailyAt('01:00');

// Close the previous leave year on Jan 1 (carry forward remaining balances).
Schedule::command('leave:year-end')->yearlyOn(1, 1, '02:00');
