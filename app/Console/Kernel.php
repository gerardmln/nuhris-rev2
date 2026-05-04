<?php

namespace App\Console;

use App\Console\Commands\ResetAnnualLeaveBalances;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Automatic leave balance reset is disabled.
        // Leave balances are now managed manually via the Admin Leave Management interface.
        // Use the "Reset All Used Leave" button to manually reset used leave balances.
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
