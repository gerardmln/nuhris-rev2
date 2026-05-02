<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveBalanceService
{
    /**
     * Leave types that affect the remaining balance (deductible).
     * All others are tracked but not deducted.
     */
    private const DEDUCTIBLE_LEAVE_TYPES = [
        'Vacation Leave',
        'Sick Leave',
        'Emergency Leave',
    ];

    /**
     * Base leave credits for each employment type.
     */
    private const BASE_CREDITS = [
        'faculty' => [
            'Vacation Leave' => 11,
            'Sick Leave' => 11,
            'Emergency Leave' => 3,
        ],
        'asp' => [
            'Vacation Leave' => 9,
            'Sick Leave' => 9,
            'Emergency Leave' => 3,
        ],
    ];

    /**
     * Maximum leave credits (except EL which is fixed).
     */
    private const MAX_CREDITS = [
        'Vacation Leave' => 15,
        'Sick Leave' => 15,
        'Emergency Leave' => 3, // Fixed, never increments
    ];

    /**
     * Yearly increment for regular employees with 1+ year service.
     */
    private const YEARLY_INCREMENT = [
        'Vacation Leave' => 2,
        'Sick Leave' => 2,
        'Emergency Leave' => 0, // No increment
    ];

    /**
     * Initialize or update leave balances for an employee.
     * This should be called when:
     * - A new employee is created
     * - An employee's employment type changes
     * - Yearly accrual needs to be processed
     */
    public function initializeOrUpdateBalance(Employee $employee): void
    {
        $employmentType = strtolower(trim($employee->employment_type ?? ''));
        
        // Determine if Faculty or ASP
        $typeKey = $this->determineEmployeeTypeKey($employmentType);
        
        if (!$typeKey) {
            // Part-time and unknown employee types should not keep leave balance rows.
            LeaveBalance::query()->where('employee_id', $employee->id)->delete();
            return;
        }

        // Get base credits for this employment type
        $baseCredits = self::BASE_CREDITS[$typeKey];

        // For each deductible leave type, calculate total credits
        foreach ($baseCredits as $leaveType => $baseAmount) {
            // Calculate yearly increments if employee is regular
            $totalCredits = $this->calculateTotalCredits($employee, $leaveType, $baseAmount);

            // Calculate used days from approved leave requests
            $usedDays = $this->calculateUsedDays($employee, $leaveType);

            // Calculate remaining balance
            $remainingDays = max(0, $totalCredits - $usedDays);

            // Update or create balance record
            LeaveBalance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type' => $leaveType,
                ],
                [
                    'remaining_days' => $remainingDays,
                ]
            );
        }
    }

    /**
     * Determine the employee type key (faculty or asp).
     */
    private function determineEmployeeTypeKey(string $employmentType): ?string
    {
        if (str_contains($employmentType, 'faculty')) {
            return 'faculty';
        }

        if (str_contains($employmentType, 'asp') || 
            str_contains($employmentType, 'admin support')) {
            return 'asp';
        }

        if (str_contains($employmentType, 'part-time') ||
            str_contains($employmentType, 'part time') ||
            str_contains($employmentType, 'parttime')) {
            return null;
        }

        return null;
    }

    /**
     * Calculate total credits including yearly accrual.
     * 
     * Rules:
     * - Start with base credits
     * - For each complete year of service (if regular):
     *   - Add yearly increment
     *   - Stop when reaching maximum
     */
    private function calculateTotalCredits(Employee $employee, string $leaveType, int $baseAmount): float
    {
        $totalCredits = (float) $baseAmount;

        // Only accrue if employee is regular with 1+ year service
        if (!$this->isRegularWithYearService($employee)) {
            return $totalCredits;
        }

        // Calculate how many complete years of service
        $yearsOfService = $this->getYearsOfService($employee);

        // For each complete year beyond the first, add increment
        if ($yearsOfService >= 1) {
            $increment = self::YEARLY_INCREMENT[$leaveType] ?? 0;
            $maxCredits = self::MAX_CREDITS[$leaveType] ?? PHP_INT_MAX;

            $completeYears = floor($yearsOfService);
            
            for ($i = 0; $i < $completeYears; $i++) {
                if ($totalCredits + $increment <= $maxCredits) {
                    $totalCredits += $increment;
                } else {
                    $totalCredits = $maxCredits;
                    break;
                }
            }
        }

        return $totalCredits;
    }

    /**
     * Check if employee is regular with at least 1 year of service.
     */
    private function isRegularWithYearService(Employee $employee): bool
    {
        $employmentType = strtoupper((string) $employee->employment_type);

        // Part-time employees are never regular
        if (str_contains($employmentType, 'PART-TIME') || 
            str_contains($employmentType, 'PART TIME') || 
            str_contains($employmentType, 'PARTTIME')) {
            return false;
        }

        if (!$employee->hire_date) {
            return false;
        }

        $hireDate = Carbon::parse($employee->hire_date);
        $referenceDate = now();

        // Faculty: regular if hired at least 1 year ago
        if (str_contains($employmentType, 'FACULTY')) {
            return $hireDate->lte($referenceDate->copy()->subYear());
        }

        // ASP: regular if hired at least 6 months ago
        if (str_contains($employmentType, 'ASP') || 
            str_contains($employmentType, 'ADMIN SUPPORT')) {
            return $hireDate->lte($referenceDate->copy()->subMonths(6));
        }

        return false;
    }

    /**
     * Get years of service for an employee.
     */
    private function getYearsOfService(Employee $employee): float
    {
        if (!$employee->hire_date) {
            return 0;
        }

        $hireDate = Carbon::parse($employee->hire_date);
        return $hireDate->diffInYears(now(), true);
    }

    /**
     * Calculate how many days of a specific leave type have been used.
     * Only counts approved leave requests.
     */
    private function calculateUsedDays(Employee $employee, string $leaveType): float
    {
        return (float) LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type', $leaveType)
            ->where('status', 'approved')
            ->sum('days_deducted');
    }

    /**
     * Get comprehensive leave usage breakdown for an employee.
     * Returns all leave types with their usage counts, separated by deductible vs tracked-only.
     */
    public function getLeaveUsageBreakdown(Employee $employee): array
    {
        $allLeaveTypes = [
            'Vacation Leave',
            'Bereavement Leave',
            'Sick Leave',
            'Training Leave',
            'Official Business',
            'Work From Home',
            'Birthday Leave',
            'Emergency Leave',
            'Maternity Leave',
            'Solo Parent Leave',
            'Special Leave for Women',
            'Paternity Leave',
            'Study Leave With Pay',
            'Study Leave Without Pay',
            'Leave Without Pay',
            'Research Leave',
        ];

        $approvedRequests = $employee->leaveRequests()
            ->where('status', 'approved')
            ->get()
            ->groupBy('leave_type');

        $breakdown = [
            'deductible' => [],
            'tracked_only' => [],
        ];

        foreach ($allLeaveTypes as $leaveType) {
            $requests = $approvedRequests->get($leaveType, collect());
            $totalDays = (float) $requests->sum('days_deducted');
            $count = $requests->count();

            $entry = [
                'type' => $leaveType,
                'days_used' => $totalDays,
                'count' => $count,
            ];

            if (in_array($leaveType, self::DEDUCTIBLE_LEAVE_TYPES, true)) {
                $breakdown['deductible'][] = $entry;
            } else {
                $breakdown['tracked_only'][] = $entry;
            }
        }

        return $breakdown;
    }

    /**
     * Get remaining balance for a specific leave type.
     */
    public function getRemainingBalance(Employee $employee, string $leaveType): float
    {
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type', $leaveType)
            ->first();

        return $balance ? (float) $balance->remaining_days : 0;
    }

    /**
     * Get all remaining balances for deductible leave types.
     */
    public function getDeductibleLeaveBalances(Employee $employee): Collection
    {
        return $employee->leaveBalances()
            ->whereIn('leave_type', self::DEDUCTIBLE_LEAVE_TYPES)
            ->orderBy('leave_type')
            ->get()
            ->map(fn ($balance) => [
                'type' => $balance->leave_type,
                'remaining' => $balance->remaining_days,
            ]);
    }

    /**
     * Check if a leave type is deductible (affects balance).
     */
    public function isDeductibleLeaveType(string $leaveType): bool
    {
        return in_array($leaveType, self::DEDUCTIBLE_LEAVE_TYPES, true);
    }

    /**
     * Get list of all deductible leave types.
     */
    public static function getDeductibleLeaveTypes(): array
    {
        return self::DEDUCTIBLE_LEAVE_TYPES;
    }
}
