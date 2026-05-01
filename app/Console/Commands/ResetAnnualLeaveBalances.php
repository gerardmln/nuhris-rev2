<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ResetAnnualLeaveBalances extends Command
{
    protected $signature = 'leaves:reset-annual';
    protected $description = 'Reset annual leave balances (vacation, sick, emergency) on employee hiring anniversaries';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $resetCount = 0;
        $balanceUpdates = 0;

        // Find employees whose hiring anniversary is today
        $employees = Employee::whereNotNull('hire_date')
            ->get()
            ->filter(function (Employee $employee) use ($today) {
                if (!$employee->hire_date) {
                    return false;
                }
                
                $hireDate = Carbon::parse($employee->hire_date);
                return $hireDate->month === $today->month && $hireDate->day === $today->day;
            });

        foreach ($employees as $employee) {
            // Reset Vacation Leave to 15 days
            $vacationBalance = LeaveBalance::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type' => 'Vacation Leave'],
                ['remaining_days' => 15]
            );
            $balanceUpdates++;

            // Reset Sick Leave to 15 days
            $sickBalance = LeaveBalance::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type' => 'Sick Leave'],
                ['remaining_days' => 15]
            );
            $balanceUpdates++;

            // Reset Emergency Leave to 5 days
            $emergencyBalance = LeaveBalance::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type' => 'Emergency Leave'],
                ['remaining_days' => 5]
            );
            $balanceUpdates++;

            $resetCount++;

            $this->info("Reset leave balances for {$employee->full_name} (ID: {$employee->employee_id})");
        }

        if ($resetCount === 0) {
            $this->info('No employees have a hiring anniversary today.');
        } else {
            $this->info("✓ Successfully reset leave balances for {$resetCount} employee(s) ({$balanceUpdates} balance record(s) updated).");
        }

        return self::SUCCESS;
    }
}
