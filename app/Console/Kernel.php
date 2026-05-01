<?php

namespace App\Console;

use App\Console\Commands\ResetAnnualLeaveBalances;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Reset annual leave balances every day at 1:00 AM
        // This checks all employees and resets balances for those with hiring anniversaries
        $schedule->command(ResetAnnualLeaveBalances::class)->dailyAt('01:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
