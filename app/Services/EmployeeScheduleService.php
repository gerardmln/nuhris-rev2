<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\AttendanceRecord;
use App\Models\EmployeeScheduleDay;
use App\Models\EmployeeScheduleSubmission;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class EmployeeScheduleService
{
    /**
     * @return array<int, array{index:int,key:string,label:string}>
     */
    public function weeklyDays(): array
    {
        return [
            ['index' => 1, 'key' => 'monday', 'label' => 'Monday'],
            ['index' => 2, 'key' => 'tuesday', 'label' => 'Tuesday'],
            ['index' => 3, 'key' => 'wednesday', 'label' => 'Wednesday'],
            ['index' => 4, 'key' => 'thursday', 'label' => 'Thursday'],
            ['index' => 5, 'key' => 'friday', 'label' => 'Friday'],
            ['index' => 6, 'key' => 'saturday', 'label' => 'Saturday'],
        ];
    }

    public function currentSubmission(Employee $employee): ?EmployeeScheduleSubmission
    {
        return EmployeeScheduleSubmission::query()
            ->with('days')
            ->where('employee_id', $employee->id)
            ->latest('submitted_at')
            ->first();
    }

    public function approvedSubmissionForDate(Employee $employee, Carbon $date): ?EmployeeScheduleSubmission
    {
        return EmployeeScheduleSubmission::query()
            ->with('days')
            ->where('employee_id', $employee->id)
            ->where('status', EmployeeScheduleSubmission::STATUS_APPROVED)
            ->latest('submitted_at')
            ->first();
    }

    public function approvedLeaveForDate(Employee $employee, Carbon $date): ?LeaveRequest
    {
        $dateString = $date->toDateString();

        return $employee->leaveRequests()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->orderByDesc('start_date')
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * @param  array<string, array<string, string|null>>  $days
     * @return array<int, array<string, mixed>>
     */
    public function normalizeWeeklyInput(array $days): array
    {
        $normalized = [];

        foreach ($this->weeklyDays() as $day) {
            $dayInput = $days[$day['key']] ?? [];
            $mode = (string) ($dayInput['mode'] ?? 'no_work');
            $hasWork = $mode === 'with_work';

            $normalized[] = [
                'day_name' => $day['label'],
                'day_index' => $day['index'],
                'has_work' => $hasWork,
                'time_in' => $hasWork ? $this->normalizeTimeInput($dayInput['time_in'] ?? null) : null,
                'time_out' => $hasWork ? $this->normalizeTimeInput($dayInput['time_out'] ?? null) : null,
            ];
        }

        return $normalized;
    }

    /**
     * @return array{schedule_status:string, schedule_notes:?string, scheduled_time_in:?string, scheduled_time_out:?string, tardiness_minutes:int, undertime_minutes:int, overtime_minutes:int, status:string}
     */
    public function evaluateDailyRecord(Employee $employee, Carbon $date, ?string $timeIn, ?string $timeOut): array
    {
        $approvedLeave = $this->approvedLeaveForDate($employee, $date);

        if ($approvedLeave) {
            return $this->evaluateApprovedLeaveRecord($employee, $approvedLeave, $date, $timeIn, $timeOut);
        }

        $submission = $this->approvedSubmissionForDate($employee, $date);

        if (! $submission) {
            return [
                'schedule_status' => 'no_schedule',
                'schedule_notes' => 'No schedule available',
                'scheduled_time_in' => null,
                'scheduled_time_out' => null,
                'tardiness_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => 'absent',
            ];
        }

        $day = $submission->days->firstWhere('day_index', (int) $date->dayOfWeekIso);

        if (! $day) {
            return [
                'schedule_status' => 'no_schedule',
                'schedule_notes' => 'No schedule available',
                'scheduled_time_in' => null,
                'scheduled_time_out' => null,
                'tardiness_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => 'absent',
            ];
        }

        if (! $day->has_work) {
            return [
                'schedule_status' => 'non_working_day',
                'schedule_notes' => 'Non-working day',
                'scheduled_time_in' => null,
                'scheduled_time_out' => null,
                'tardiness_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => $timeIn || $timeOut ? 'present' : 'absent',
            ];
        }

        $scheduledIn = $this->parseTime($day->time_in);
        $scheduledOut = $this->parseTime($day->time_out);
        $actualIn = $this->parseTime($timeIn);
        $actualOut = $this->parseTime($timeOut);

        $tardiness = 0;
        $undertime = 0;
        $overtime = 0;

        if ($actualIn && $scheduledIn && $actualIn->gt($scheduledIn)) {
            $tardiness = $scheduledIn->diffInMinutes($actualIn);
        }

        if ($actualOut && $scheduledOut) {
            if ($actualOut->lt($scheduledOut)) {
                $undertime = $actualOut->diffInMinutes($scheduledOut);
            } elseif ($actualOut->gt($scheduledOut)) {
                $overtime = $scheduledOut->diffInMinutes($actualOut);
            }
        }

        return [
            'schedule_status' => 'validated',
            'schedule_notes' => null,
            'scheduled_time_in' => $scheduledIn?->format('H:i:s'),
            'scheduled_time_out' => $scheduledOut?->format('H:i:s'),
            'tardiness_minutes' => $tardiness,
            'undertime_minutes' => $undertime,
            'overtime_minutes' => $overtime,
            'status' => $actualIn ? 'present' : 'absent',
        ];
    }

    /**
     * @return array{schedule_status:string, schedule_notes:?string, scheduled_time_in:?string, scheduled_time_out:?string, tardiness_minutes:int, undertime_minutes:int, overtime_minutes:int, status:string}
     */
    private function evaluateApprovedLeaveRecord(Employee $employee, LeaveRequest $leave, Carbon $date, ?string $timeIn, ?string $timeOut): array
    {
        $leaveMonitoring = app(LeaveMonitoringService::class);
        $leaveBalanceService = app(LeaveBalanceService::class);
        $policy = $leaveMonitoring->resolvePolicy((string) $leave->leave_type);
        $leaveLabel = $policy['label'] ?? trim((string) $leave->leave_type) ?: 'Leave';
        $storageType = $policy['storage_leave_type'] ?? $leaveLabel;
        $isRegular = $leaveMonitoring->isRegularEmployee($employee, $date);

        $status = 'present';
        $notes = 'Approved leave: '.$leaveLabel;

        if (($policy['requires_regular'] ?? false) && ! $isRegular) {
            $status = 'absent';
            $notes = 'Approved leave (non-regular): '.$leaveLabel;
        } elseif (! empty($policy['requires_wfh_submission'])) {
            $status = ($timeIn || $timeOut) ? 'present' : 'absent';
            $notes = 'Approved leave: '.$leaveLabel;
        } elseif ($leaveBalanceService->isDeductibleLeaveType($storageType) && $leaveBalanceService->getRemainingBalance($employee, $storageType) <= 0) {
            $status = 'absent';
            $notes = 'Approved leave exceeded balance: '.$leaveLabel;
        }

        return [
            'schedule_status' => 'validated',
            'schedule_notes' => $notes,
            'scheduled_time_in' => null,
            'scheduled_time_out' => null,
            'tardiness_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
            'status' => $status,
        ];
    }

    public function mapDaysByIndex(iterable $days): array
    {
        $mapped = [];

        foreach ($days as $day) {
            $mapped[(int) $day->day_index] = $day;
        }

        return $mapped;
    }

    public function summarizeSubmission(?EmployeeScheduleSubmission $submission): string
    {
        if (! $submission) {
            return 'No approved schedule available';
        }

        $parts = [];

        $termLabel = $submission->term_label ?: $submission->semester_label;

        if ($termLabel) {
            $parts[] = 'Term: '.$termLabel;
        }

        foreach ($submission->days as $day) {
            $parts[] = $day->day_name.': '.($day->has_work
                ? (($day->time_in?->format('h:i A') ?? 'N/A').' - '.($day->time_out?->format('h:i A') ?? 'N/A'))
                : 'No Work');
        }

        return implode(' | ', $parts);
    }

    public function countDtrAbsences(Employee $employee, Carbon $startDate, Carbon $endDate): int
    {
        return $this->countDtrAbsencesWithContext($employee, $startDate, $endDate);
    }

    /**
     * @param ?Collection<int, LeaveRequest> $approvedLeaves
     */
    public function countDtrAbsencesWithContext(Employee $employee, Carbon $startDate, Carbon $endDate, ?Collection $approvedLeaves = null, ?EmployeeScheduleSubmission $submission = null): int
    {
        $periodStart = $startDate->copy()->startOfDay();
        $periodEnd = $endDate->copy()->startOfDay();

        if ($periodEnd->lt($periodStart)) {
            return 0;
        }

        $attendanceRecords = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('record_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->record_date->toDateString());

        $submission ??= $this->approvedSubmissionForDate($employee, $periodStart);
        $absences = 0;

        foreach (CarbonPeriod::create($periodStart, $periodEnd) as $date) {
            if ($date->isAfter(now())) {
                continue;
            }

            if ($date->dayOfWeekIso === 7) {
                continue;
            }

            if ($approvedLeaves && $approvedLeaves->contains(fn (LeaveRequest $leave) => $leave->start_date?->lte($date) && $leave->end_date?->gte($date))) {
                continue;
            }

            if ($submission) {
                $scheduleDay = $submission->days->firstWhere('day_index', $date->dayOfWeekIso);

                if ($scheduleDay && ! $scheduleDay->has_work) {
                    continue;
                }
            }

            $dateKey = $date->toDateString();

            if (! $attendanceRecords->has($dateKey)) {
                $absences++;
                continue;
            }

            $record = $attendanceRecords->get($dateKey);

            if ($record->status === 'absent' && ! in_array($record->schedule_status, ['non_working_day', 'no_schedule'], true)) {
                $absences++;
            }
        }

        return $absences;
    }

    private function normalizeTimeInput(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $this->parseTime($value)?->format('H:i:s');
    }

    private function parseTime(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i', substr($value, 0, 5));
        } catch (\Throwable) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }
    }
}