<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\LeaveBalanceService;
use Illuminate\Console\Command;

class InitializeLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave-balance:initialize {--employee-id= : Initialize for specific employee} {--force : Force reinitialize all employees}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Initialize or update leave balances for employees based on their tenure and employment type';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $leaveBalanceService = app(LeaveBalanceService::class);

        $employeeId = $this->option('employee-id');
        $force = $this->option('force');

        if ($employeeId) {
            // Initialize for specific employee
            $employee = Employee::find($employeeId);
            if (!$employee) {
                $this->error("Employee with ID {$employeeId} not found.");
                return 1;
            }

            $leaveBalanceService->initializeOrUpdateBalance($employee);
            $this->info("Leave balances initialized for {$employee->full_name}");
            return 0;
        }

        if (!$force && !$this->confirm('Initialize leave balances for all employees?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $employees = Employee::all();
        $count = 0;

        foreach ($employees as $employee) {
            $leaveBalanceService->initializeOrUpdateBalance($employee);
            $count++;

            if ($count % 10 === 0) {
                $this->line("Processed {$count} employees...");
            }
        }

        $this->info("Leave balances initialized for {$count} employees.");
        return 0;
    }
}
