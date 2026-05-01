<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveMonitoringService
{
    /**
     * @return array{key:string,label:string,requires_regular:bool,track_in_leave_module:bool,auto_mark_present:bool,requires_wfh_submission:bool,storage_leave_type:string}
     */
    public function resolvePolicy(string $application, ?string $category = null): array
    {
        $source = strtoupper(trim($application.' '.($category ?? '')));

        if ($this->contains($source, ['WORK FROM HOME', 'WFH'])) {
            return $this->policy('work_from_home', 'Work From Home', false, false, false, true, 'Work From Home');
        }

        if ($this->contains($source, ['TRAINING LEAVE'])) {
            return $this->policy('training_leave', 'Training Leave', false, false, true, false, 'Training Leave');
        }

        if ($this->contains($source, ['OFFICIAL BUSINESS'])) {
            return $this->policy('official_business', 'Official Business', false, false, true, false, 'Official Business');
        }

        if ($this->contains($source, ['VACATION'])) {
            return $this->policy('vacation_leave', 'Vacation Leave', true, true, true, false, 'Vacation Leave');
        }

        if ($this->contains($source, ['BEREAVEMENT'])) {
            return $this->policy('bereavement_leave', 'Bereavement Leave', true, false, true, false, 'Bereavement Leave');
        }

        if ($this->contains($source, ['SICK'])) {
            return $this->policy('sick_leave', 'Sick Leave', true, true, true, false, 'Sick Leave');
        }

        if ($this->contains($source, ['BIRTHDAY'])) {
            // Birthday Leave should be recorded as Emergency Leave in Leave Management.
            return $this->policy('birthday_leave', 'Birthday Leave', true, true, true, false, 'Emergency Leave');
        }

        if ($this->contains($source, ['EMERGENCY'])) {
            return $this->policy('emergency_leave', 'Emergency Leave', true, true, true, false, 'Emergency Leave');
        }

        if ($this->contains($source, ['MATERNITY'])) {
            return $this->policy('maternity_leave', 'Maternity Leave', false, false, true, false, 'Maternity Leave');
        }

        if ($this->contains($source, ['SOLO PARENT'])) {
            return $this->policy('solo_parent_leave', 'Solo Parent Leave', false, false, true, false, 'Solo Parent Leave');
        }

        if ($this->contains($source, ['SPECIAL LEAVE FOR WOMEN', 'SPECIAL WOMEN'])) {
            return $this->policy('special_leave_for_women', 'Special Leave for Women', false, false, true, false, 'Special Leave for Women');
        }

        if ($this->contains($source, ['PATERNITY'])) {
            return $this->policy('paternity_leave', 'Paternity Leave', false, false, true, false, 'Paternity Leave');
        }

        if ($this->contains($source, ['STUDY LEAVE WITH PAY'])) {
            return $this->policy('study_leave_with_pay', 'Study Leave With Pay', true, false, true, false, 'Study Leave With Pay');
        }

        if ($this->contains($source, ['STUDY LEAVE WITHOUT PAY'])) {
            return $this->policy('study_leave_without_pay', 'Study Leave Without Pay', false, false, false, false, 'Study Leave Without Pay');
        }

        if ($this->contains($source, ['LEAVE WITHOUT PAY', 'LWOP'])) {
            return $this->policy('leave_without_pay', 'Leave Without Pay', false, false, false, false, 'Leave Without Pay');
        }

        if ($this->contains($source, ['RESEARCH'])) {
            return $this->policy('research_leave', 'Research Leave', false, false, true, false, 'Research Leave');
        }

        // Default fallback: preserve current behavior for unknown leave types.
        return $this->policy('other', trim($application) !== '' ? trim($application) : 'Leave', false, true, false, false, trim($application) !== '' ? trim($application) : 'Leave');
    }

    public function isEligibleForPolicy(Employee $employee, array $policy, ?Carbon $referenceDate = null): bool
    {
        if (! ($policy['requires_regular'] ?? false)) {
            return true;
        }

        return $this->isRegularEmployee($employee, $referenceDate);
    }

    public function isRegularEmployee(Employee $employee, ?Carbon $referenceDate = null): bool
    {
        $employmentType = strtoupper((string) $employee->employment_type);

        if (str_contains($employmentType, 'PART-TIME FACULTY') || str_contains($employmentType, 'PART TIME FACULTY')) {
            return false;
        }

        if (! $employee->hire_date) {
            return false;
        }

        $referenceDate ??= now();
        $hireDate = Carbon::parse($employee->hire_date)->startOfDay();

        if (str_contains($employmentType, 'FACULTY')) {
            return $hireDate->lte($referenceDate->copy()->subYear());
        }

        if (str_contains($employmentType, 'ADMIN SUPPORT PERSONNEL') || str_contains($employmentType, 'ASP')) {
            return $hireDate->lte($referenceDate->copy()->subMonths(6));
        }

        // Unknown employment classifications are treated as non-regular for safety.
        return false;
    }

    public function autoMarkPresentIfMissing(Employee $employee, Carbon $startDate, Carbon $endDate, string $leaveLabel): int
    {
        $created = 0;

        $periodStart = $startDate->copy()->startOfDay();
        $periodEnd = $endDate->copy()->startOfDay();

        if ($periodEnd->lt($periodStart)) {
            $periodEnd = $periodStart->copy();
        }

        $period = CarbonPeriod::create($periodStart, $periodEnd);

        foreach ($period as $date) {
            $exists = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('record_date', $date->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            AttendanceRecord::create([
                'employee_id' => $employee->id,
                'record_date' => $date->toDateString(),
                'time_in' => null,
                'time_out' => null,
                'scheduled_time_in' => null,
                'scheduled_time_out' => null,
                'tardiness_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
                'schedule_status' => 'validated',
                'schedule_notes' => 'Auto-fulfilled from approved leave: '.$leaveLabel,
                'status' => 'present',
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * @return array{key:string,label:string,requires_regular:bool,track_in_leave_module:bool,auto_mark_present:bool,requires_wfh_submission:bool,storage_leave_type:string}
     */
    private function policy(
        string $key,
        string $label,
        bool $requiresRegular,
        bool $trackInLeaveModule,
        bool $autoMarkPresent,
        bool $requiresWfhSubmission,
        string $storageLeaveType
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'requires_regular' => $requiresRegular,
            'track_in_leave_module' => $trackInLeaveModule,
            'auto_mark_present' => $autoMarkPresent,
            'requires_wfh_submission' => $requiresWfhSubmission,
            'storage_leave_type' => $storageLeaveType,
        ];
    }

    /**
     * @param array<int, string> $needles
     */
    private function contains(string $source, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($source, strtoupper($needle))) {
                return true;
            }
        }

        return false;
    }
}
