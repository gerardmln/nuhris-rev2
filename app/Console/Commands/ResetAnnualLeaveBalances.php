<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetAnnualLeaveBalances extends Command
{
    protected $signature = 'leaves:reset-annual';
    protected $description = 'Reset annual leave balances (vacation, sick, emergency) on employee hiring anniversaries';

    public function handle(): int
    {
        $this->info('Automatic leave balance reset is disabled.');
        $this->info('Leave balances are now managed manually via the Admin Leave Management interface.');
        $this->info('To reset used leave balances, use the "Reset All Used Leave" button in the Admin panel.');

        return self::SUCCESS;
    }
}
