<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WfhMonitoringSubmission;
use App\Services\EmployeeScheduleService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveMonitoringService;
use App\Services\SupabaseStorageService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;

class OperationsController extends Controller
{
    protected SupabaseStorageService $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * ========== CREDENTIAL MANAGEMENT (ADMIN-ONLY) ==========
     */

    public function credentials(Request $request): View
    {
        $statusFilter = $request->string('status')->toString() ?: 'pending';

        $query = EmployeeCredential::query()
            ->with(['employee.department', 'reviewer'])
            ->latest('updated_at');

        if ($statusFilter === 'expiring') {
            $query->where('status', 'verified');
        } elseif (in_array($statusFilter, ['pending', 'verified', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        $credentials = $query->get()->map(function (EmployeeCredential $credential) {
            return [
                'id' => $credential->id,
                'employee_id' => $credential->employee_id,
                'employee_name' => $credential->employee?->full_name ?? '—',
                'employee_email' => $credential->employee?->email ?? '',
                'department' => $credential->employee?->department?->name ?? 'Unassigned',
                'type_label' => $credential->typeLabel(),
                'title' => $credential->title ?: $credential->typeLabel(),
                'description' => $credential->description,
                'expires_at' => $credential->effectiveExpiresAt()?->format('M d, Y'),
                'submitted_at' => $credential->created_at?->format('M d, Y h:i A'),
                'status' => $credential->status,
                'is_expiring_soon' => $credential->status === 'verified' && $credential->isExpiringSoon(),
                'has_file' => !empty($credential->file_path),
                'original_filename' => $credential->original_filename,
                'review_notes' => $credential->review_notes,
                'reviewer_name' => $credential->reviewer?->name,
                'reviewed_at' => $credential->reviewed_at?->format('M d, Y h:i A'),
            ];
        });

        if ($statusFilter === 'expiring') {
            $credentials = $credentials
                ->filter(fn (array $credential) => !empty($credential['is_expiring_soon']))
                ->values();
        }

        $counts = [
            'pending' => EmployeeCredential::query()->where('status', 'pending')->count(),
            'verified' => EmployeeCredential::query()->where('status', 'verified')->count(),
            'rejected' => EmployeeCredential::query()->where('status', 'rejected')->count(),
            'total' => EmployeeCredential::query()->count(),
        ];

        return view('admin.credentials.index', [
            'credentials' => $credentials,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function editCredential(EmployeeCredential $credential): View
    {
        return view('admin.credentials.edit', [
            'credential' => $credential,
        ]);
    }

    public function updateCredential(Request $request, EmployeeCredential $credential): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'review_notes' => 'nullable|string',
        ]);

        $credential->update($validated);

        return redirect()->route('admin.credentials.index')
            ->with('success', 'Credential updated successfully.');
    }

    public function deleteCredential(EmployeeCredential $credential): RedirectResponse
    {
        $employeeId = $credential->employee_id;
        $employeeName = $credential->employee?->full_name ?? 'Unknown Employee';

        // Delete file from storage if exists
        if (!empty($credential->file_path) && $this->storage->isEnabled()) {
            $this->storage->delete($credential->file_path);
        }

        $credential->forceDelete();

        return redirect()->route('admin.credentials.index')
            ->with('success', "Credential deleted for {$employeeName}.");
    }

    public function clearAllCredentials(): RedirectResponse
    {
        DB::transaction(function () {
            $credentials = EmployeeCredential::all();
            foreach ($credentials as $credential) {
                if (!empty($credential->file_path) && $this->storage->isEnabled()) {
                    $this->storage->delete($credential->file_path);
                }
            }
            EmployeeCredential::query()->forceDelete();
        });

        return redirect()->route('admin.credentials.index')
            ->with('success', 'All credentials have been cleared.');
    }

    /**
     * ========== DTR / TIMEKEEPING EDITING (ADMIN-ONLY) ==========
     */

    public function dtrIndex(Request $request): View
    {
        $employeeId = $request->integer('employee_id');
        
        // Parse dates with defaults
        $dateFromInput = $request->get('date_from');
        $dateToInput = $request->get('date_to');
        
        $dateFrom = $dateFromInput 
            ? Carbon::createFromFormat('Y-m-d', $dateFromInput) 
            : Carbon::now()->startOfMonth();
        
        $dateTo = $dateToInput 
            ? Carbon::createFromFormat('Y-m-d', $dateToInput) 
            : Carbon::now()->endOfMonth();

        $employee = $employeeId ? Employee::query()->findOrFail($employeeId) : null;

        $records = AttendanceRecord::query()
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->whereBetween('record_date', [$dateFrom, $dateTo])
            ->orderBy('record_date', 'desc')
            ->get();

        // Build employee cards similar to HR Timekeeping UI
        $employees = Employee::query()->orderBy('last_name')->orderBy('first_name')->get();

        $attendanceStats = AttendanceRecord::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('record_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($group) => [
                'present' => $group->where('status', 'present')->count(),
                'tardiness' => (int) $group->sum('tardiness_minutes'),
                'has_data' => $group->isNotEmpty(),
            ]);

        $employeeCards = $employees->map(function (Employee $emp) use ($attendanceStats, $dateFrom, $dateTo) {
            $stats = $attendanceStats->get($emp->id, ['present' => 0, 'tardiness' => 0, 'has_data' => false]);
            $scheduleService = app(EmployeeScheduleService::class);

            return [
                'id' => $emp->id,
                'initials' => str($emp->full_name)->explode(' ')->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join(''),
                'name' => $emp->full_name,
                'department' => $emp->department?->name ?? 'Unassigned',
                'present' => $stats['present'],
                'tardiness' => $stats['tardiness'],
                'absences' => $scheduleService->countDtrAbsences($emp, $dateFrom, $dateTo),
                'has_data' => $stats['has_data'],
                'schedule_summary' => $scheduleService->summarizeSubmission($scheduleService->currentSubmission($emp)),
            ];
        });

        return view('admin.dtr.index', [
            'records' => $records,
            'employee' => $employee,
            'employees' => $employees,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'employeeCards' => $employeeCards,
        ]);
    }

    public function editDtrRecord(AttendanceRecord $record): View
    {
        $employee = $record->employee;
        $absenceCount = 0;

        if ($employee && $record->record_date) {
            $scheduleService = app(EmployeeScheduleService::class);
            $monthStart = $record->record_date->copy()->startOfMonth();
            $monthEnd = $record->record_date->copy()->endOfMonth();

            $absenceCount = $scheduleService->countDtrAbsences($employee, $monthStart, $monthEnd);
        }

        return view('admin.dtr.edit', [
            'record' => $record,
            'absenceCount' => $absenceCount,
        ]);
    }

    public function updateDtrRecord(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'status' => 'nullable|string|in:present,absent,late,undertime,overtime',
            'remarks' => 'nullable|string|max:500',
        ]);

        $record->update($validated);

        return redirect()->route('admin.dtr.index')
            ->with('success', 'DTR record updated successfully.');
    }

    /**
     * ========== WFH MANAGEMENT (ADMIN-ONLY) ==========
     */

    public function wfhIndex(): View
    {
        $submissions = WfhMonitoringSubmission::query()
            ->with('employee')
            ->latest('wfh_date')
            ->get();

        return view('admin.wfh-monitoring.index', [
            'submissions' => $submissions,
        ]);
    }

    public function clearAllWfh(): RedirectResponse
    {
        $count = WfhMonitoringSubmission::query()->count();

        // Delete files from storage
        $submissions = WfhMonitoringSubmission::all();
        foreach ($submissions as $submission) {
            if (!empty($submission->file_path) && $this->storage->isEnabled()) {
                $this->storage->delete($submission->file_path);
            }
        }

        WfhMonitoringSubmission::query()->forceDelete();

        // Also remove corresponding attendance records
        AttendanceRecord::query()
            ->where('schedule_status', 'wfh')
            ->delete();

        return redirect()->route('admin.wfh-monitoring.index')
            ->with('success', "Cleared {$count} WFH records and corresponding attendance.");
    }

    /**
     * ========== LEAVE MANAGEMENT (ADMIN-ONLY) ==========
     */

    public function leaveIndex(Request $request): View
    {
        $search = $request->string('search')->toString();
        $departmentId = $request->string('department_id')->toString();
        $employeeClass = $request->string('employee_class')->toString() ?: 'all';
        $leaveMonitoringService = app(LeaveMonitoringService::class);
        $leaveBalanceService = app(LeaveBalanceService::class);

        $employeesQuery = Employee::query()
            ->with([
                'department',
                'leaveRequests',
            ])
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($nested) use ($searchTerm) {
                    $nested
                        ->where('employee_id', 'like', "%{$searchTerm}%")
                        ->orWhere('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->when($departmentId === 'asp', function ($query) {
                $query->where(function ($nested) {
                    $nested
                        ->where('employment_type', 'Admin Support Personnel')
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'ASP'));
                });
            })
            ->when(filled($departmentId) && $departmentId !== 'asp', fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('last_name')
            ->orderBy('first_name');

        $leaveMonitoringService->applyEmployeeClassFilter($employeesQuery, $employeeClass);

        $employees = $employeesQuery->get();

        $employees->each(function (Employee $employee) use ($leaveBalanceService) {
            $leaveBalanceService->initializeOrUpdateBalance($employee);
            $employee->unsetRelation('leaveBalances');
            $employee->load('leaveBalances');
        });

        // Pre-aggregate absence counts for the current year to avoid N+1 queries
        $yearStart = now()->startOfYear()->toDateString();
        $yearEnd = now()->endOfYear()->toDateString();
        $absencesByEmployee = AttendanceRecord::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('record_date', [$yearStart, $yearEnd])
            ->where('status', 'absent')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($group) => $group->count());

        $leaveCards = $employees->map(function (Employee $employee) use ($absencesByEmployee) {
            $balances = $employee->leaveBalances;
            $today = now()->startOfDay();
            $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date)->startOfDay() : null;
            $leaveMonitoringService = app(LeaveMonitoringService::class);
            $isRegularEmployee = $leaveMonitoringService->isRegularEmployee($employee, $today);

            $requests = $employee->leaveRequests->filter(function (LeaveRequest $request) use ($hireDate, $today) {
                if (! $request->start_date) {
                    return false;
                }

                $startDate = $request->start_date->copy()->startOfDay();

                if ($hireDate && $startDate->lt($hireDate)) {
                    return false;
                }

                return $startDate->lte($today);
            });

            $approvedRequests = $requests->where('status', 'approved');
            $usedByType = $approvedRequests->groupBy(fn ($r) => strtolower((string) $r->leave_type));

            $vacationUsed = (float) collect(['vacation leave', 'vacation', 'vl'])
                ->flatMap(fn ($key) => $usedByType->get($key, collect()))
                ->sum('days_deducted');

            $sickUsed = (float) collect(['sick leave', 'sick', 'sl'])
                ->flatMap(fn ($key) => $usedByType->get($key, collect()))
                ->sum('days_deducted');

            $emergencyUsed = (float) collect(['emergency leave', 'emergency', 'el'])
                ->flatMap(fn ($key) => $usedByType->get($key, collect()))
                ->sum('days_deducted');

            $used = $vacationUsed + $sickUsed + $emergencyUsed;
            $remaining = (float) $balances->sum('remaining_days');

            $balanceByType = $balances->keyBy(fn ($b) => strtolower((string) $b->leave_type));
            $lookup = function (array $keys) use ($balanceByType) {
                foreach ($keys as $key) {
                    if ($balanceByType->has($key)) {
                        return (float) $balanceByType->get($key)->remaining_days;
                    }
                }

                return null;
            };

            return [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'department' => $employee->department?->name ?? 'Unassigned',
                'initials' => str($employee->full_name)->explode(' ')->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join(''),
                'vacation_remaining' => $lookup(['vacation leave', 'vacation', 'vl']),
                'vacation_used' => $vacationUsed,
                'sick_remaining' => $lookup(['sick leave', 'sick', 'sl']),
                'sick_used' => $sickUsed,
                'emergency_remaining' => $lookup(['emergency leave', 'emergency', 'el']),
                'emergency_used' => $emergencyUsed,
                'remaining' => $remaining,
                'used' => $used,
                'carry_over' => 0,
                'has_data' => $requests->isNotEmpty() || $balances->isNotEmpty(),
                'leave_types' => $usedByType->keys()->toArray(),
                'employee_status' => $isRegularEmployee ? 'regular' : 'non-regular',
                'employee_status_label' => $isRegularEmployee ? 'Regular' : 'Non-Regular',
                'absences' => $absencesByEmployee->get($employee->id, 0),
            ];
        });

        $totalUsed = (float) $leaveCards->sum('used');
        $totalVacationUsed = (float) $leaveCards->sum('vacation_used');
        $totalSickUsed = (float) $leaveCards->sum('sick_used');

        $monthOptions = collect(range(0, 11))
            ->map(function ($offset) {
                $date = now()->startOfMonth()->subMonths($offset);

                return [
                    'value' => $date->format('Y-m'),
                    'label' => $date->format('F Y'),
                ];
            })
            ->values();

        return view('admin.leave-management.index', [
            'leaveCards' => $leaveCards,
            'departments' => Department::query()->facultySchools()->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'employee_class' => $employeeClass,
            ],
            'monthOptions' => $monthOptions,
            'stats' => [
                'total_employees' => $leaveCards->count(),
                'vacation_used' => (int) round($totalVacationUsed),
                'sick_used' => (int) round($totalSickUsed),
                'current_year' => now()->year,
            ],
        ]);
    }

    public function clearAllLeaves(): RedirectResponse
    {
        DB::transaction(function () {
            LeaveRequest::query()->forceDelete();
            LeaveBalance::query()->forceDelete();
        });

        return redirect()->route('admin.leave.index')
            ->with('success', 'All leave requests and balances have been cleared.');
    }

    public function resetEmployeeLeaves(Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($employee) {
            LeaveRequest::query()->where('employee_id', $employee->id)->forceDelete();
            LeaveBalance::query()->where('employee_id', $employee->id)->forceDelete();
        });

        return redirect()->route('admin.leave.index')
            ->with('success', "Leave data for {$employee->full_name} has been reset.");
    }

    public function uploadLeaves(Request $request, LeaveMonitoringService $leaveMonitoringService): RedirectResponse
    {
        $validated = $request->validate([
            'leaves_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'leaves_file.required' => 'Please choose an Excel file first.',
            'leaves_file.mimes' => 'Only .xlsx or .xls files are accepted.',
            'leaves_file.max' => 'The file is too large. Maximum allowed is 10 MB.',
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['leaves_file'];

        try {
            $spreadsheet = SpreadsheetIOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Unable to read the Excel file: '.$exception->getMessage());
        }

        if (empty($rows) || count($rows) < 2) {
            return back()->with('error', 'The Excel file does not contain any data rows.');
        }

        $header = array_map(
            fn ($cell) => strtolower(trim((string) $cell)),
            $rows[0]
        );
        $dataRows = array_slice($rows, 1);

        $columnIndex = $this->resolveLeaveColumns($header);
        $missingColumns = array_keys(array_filter($columnIndex, fn ($idx) => $idx === null));

        if (! empty($missingColumns)) {
            return back()->with('error', 'Missing expected column(s) in the Excel header: '.implode(', ', $missingColumns).'. Expected headers: Leave ID, Employee ID, Employee Name, Application, Type, Date Filed, Date From, Date To, Total Hours, Status.');
        }

        $allEmployees = Employee::all();
        $byExactId = $allEmployees->keyBy('employee_id');
        $byNormalizedId = [];
        $byNameIndex = [];
        $employeeUserIdsByEmail = User::query()
            ->whereIn('email', $allEmployees->pluck('email')->filter()->unique()->values())
            ->pluck('id', 'email');

        foreach ($allEmployees as $emp) {
            $byNormalizedId[$this->normalizeEmployeeId($emp->employee_id)] = $emp;
            $lastKey = mb_strtolower(trim((string) $emp->last_name));
            $byNameIndex[$lastKey][] = $emp;
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $attendanceFulfilled = 0;
        $wfhNotifications = 0;
        $unmatched = [];
        $applied = [];
        $queuedAttendanceRows = [];
        $queuedWfhRequests = [];

        foreach ($dataRows as $rowNumber => $row) {
            if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $employeeCode = trim((string) ($row[$columnIndex['employee_id']] ?? ''));
            $employeeName = trim((string) ($row[$columnIndex['employee_name']] ?? ''));

            if ($employeeCode === '' && $employeeName === '') {
                $skipped++;
                continue;
            }

            $employee = $this->findEmployeeInMemory(
                [
                    'employee_id' => $employeeCode,
                    'name' => $employeeName,
                ],
                $byExactId,
                $byNormalizedId,
                $byNameIndex,
            );

            if (! $employee) {
                $skipped++;
                $unmatched[trim($employeeName.' ('.$employeeCode.')', ' ()')] = true;
                continue;
            }

            $startDate = $this->parseExcelDate($row[$columnIndex['date_from']] ?? null);
            $endDate = $this->parseExcelDate($row[$columnIndex['date_to']] ?? null) ?? $startDate;
            $dateFiled = $this->parseExcelDate($row[$columnIndex['date_filed']] ?? null);

            if (! $startDate) {
                $skipped++;
                continue;
            }

            $totalHours = (float) ($row[$columnIndex['total_hours']] ?? 0);
            $daysDeducted = $totalHours > 0 ? round($totalHours / 8, 2) : 0;

            $leaveType = trim((string) ($row[$columnIndex['application']] ?? 'Leave'));
            $category = trim((string) ($row[$columnIndex['type']] ?? ''));

            $statusRaw = strtoupper(trim((string) ($row[$columnIndex['status']] ?? '')));
            $status = match (true) {
                str_contains($statusRaw, 'APPROVED') && ! str_contains($statusRaw, 'DIS') => 'approved',
                str_contains($statusRaw, 'DIS') || str_contains($statusRaw, 'CANCEL') || str_contains($statusRaw, 'REJECT') => 'rejected',
                default => 'pending',
            };

            $policy = $leaveMonitoringService->resolvePolicy($leaveType, $category);
            $referenceDate = Carbon::parse($startDate);
            $isEligible = $leaveMonitoringService->isEligibleForPolicy($employee, $policy, $referenceDate);
            $isTrackedLeaveType = in_array($policy['storage_leave_type'], ['Vacation Leave', 'Sick Leave', 'Emergency Leave'], true);
            $shouldTrackInLeaveModule = $policy['track_in_leave_module'] && ($isEligible || $isTrackedLeaveType);

            $reasonParts = array_filter([$category, $statusRaw ? 'Source status: '.$statusRaw : null]);

            $attributes = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'leave_type' => $policy['storage_leave_type'],
                'days_deducted' => $daysDeducted,
                'status' => $status,
                'cutoff_date' => $dateFiled,
                'reason' => $reasonParts ? implode(' · ', $reasonParts) : null,
            ];

            $existing = LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->whereDate('start_date', $startDate)
                ->whereIn('leave_type', array_values(array_unique([$leaveType, $policy['storage_leave_type']])))
                ->first();

            if ($shouldTrackInLeaveModule) {
                if ($existing) {
                    $existing->update($attributes);
                    $updated++;
                } else {
                    LeaveRequest::create(array_merge(['employee_id' => $employee->id], $attributes));
                    $imported++;
                }
            } elseif ($existing) {
                $existing->delete();
            }

            if ($status === 'approved' && $isEligible && $policy['auto_mark_present']) {
                $this->queueAutoPresentAttendance(
                    $queuedAttendanceRows,
                    $employee,
                    Carbon::parse($startDate),
                    Carbon::parse($endDate),
                    $policy['label']
                );
            }

            if ($status === 'approved' && $isEligible && $policy['requires_wfh_submission']) {
                $this->queueWfhUploadRequiredNotification(
                    $queuedWfhRequests,
                    $employee,
                    Carbon::parse($startDate),
                    Carbon::parse($endDate),
                    $policy['label']
                );
            }

            $applied[$employee->full_name . ' (' . $employee->employee_id . ')'] = true;
        }

        $attendanceFulfilled = $this->insertQueuedAttendanceRows($queuedAttendanceRows);
        $wfhNotifications = $this->sendQueuedWfhUploadRequiredNotifications(
            $request,
            $queuedWfhRequests,
            $employeeUserIdsByEmail
        );

        $message = "Leave file processed — {$imported} new, {$updated} updated";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }
        if ($attendanceFulfilled > 0) {
            $message .= ", {$attendanceFulfilled} attendance day(s) auto-fulfilled";
        }
        if ($wfhNotifications > 0) {
            $message .= ", {$wfhNotifications} WFH notification(s) sent";
        }
        $message .= '.';

        return back()
            ->with('success', $message)
            ->with('unmatched_employees', array_keys($unmatched))
            ->with('applied_employees', array_keys($applied))
            ->with('import_stats', [
                'imported' => $imported + $updated,
                'skipped' => $skipped,
                'total_records' => count($dataRows),
            ]);
    }

    private function normalizeEmployeeId(string $id): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', trim($id)) ?? '';

        if (preg_match('/^\d+\.0+$/', $normalized)) {
            $normalized = strstr($normalized, '.', true) ?: $normalized;
        }

        return $normalized;
    }

    private function resolveLeaveColumns(array $header): array
    {
        $find = function (array $candidates) use ($header): ?int {
            foreach ($header as $idx => $label) {
                if (in_array($label, $candidates, true)) {
                    return $idx;
                }
            }

            return null;
        };

        return [
            'employee_id' => $find(['employee id', 'employee_id', 'emp id']),
            'employee_name' => $find(['employee name', 'employee_name', 'name']),
            'application' => $find(['application', 'application type', 'leave type']),
            'type' => $find(['type', 'category']),
            'date_filed' => $find(['date filed', 'filed', 'filed_on']),
            'date_from' => $find(['date from', 'from', 'start date', 'start_date']),
            'date_to' => $find(['date to', 'to', 'end date', 'end_date']),
            'total_hours' => $find(['total hours', 'hours']),
            'status' => $find(['status']),
        ];
    }

    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $datetime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);

                return $datetime->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, array<string, mixed>> $queuedAttendanceRows
     */
    private function queueAutoPresentAttendance(array &$queuedAttendanceRows, Employee $employee, Carbon $startDate, Carbon $endDate, string $leaveLabel): void
    {
        $periodStart = $startDate->copy()->startOfDay();
        $periodEnd = $endDate->copy()->startOfDay();

        if ($periodEnd->lt($periodStart)) {
            $periodEnd = $periodStart->copy();
        }

        foreach (CarbonPeriod::create($periodStart, $periodEnd) as $date) {
            $recordDate = $date->toDateString();
            $key = $employee->id.'|'.$recordDate;

            if (isset($queuedAttendanceRows[$key])) {
                continue;
            }

            $queuedAttendanceRows[$key] = [
                'employee_id' => $employee->id,
                'record_date' => $recordDate,
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
            ];
        }
    }

    /**
     * @param array<string, array<string, mixed>> $queuedAttendanceRows
     */
    private function insertQueuedAttendanceRows(array $queuedAttendanceRows): int
    {
        if (empty($queuedAttendanceRows)) {
            return 0;
        }

        $rows = array_values($queuedAttendanceRows);
        $employeeIds = array_values(array_unique(array_map(fn ($row) => (int) $row['employee_id'], $rows)));
        $recordDates = array_values(array_map(fn ($row) => (string) $row['record_date'], $rows));
        $minDate = min($recordDates);
        $maxDate = max($recordDates);

        $existingKeys = AttendanceRecord::query()
            ->select(['employee_id', 'record_date'])
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('record_date', [$minDate, $maxDate])
            ->get()
            ->mapWithKeys(fn (AttendanceRecord $record) => [
                $record->employee_id.'|'.Carbon::parse($record->record_date)->toDateString() => true,
            ]);

        $now = now();
        $toInsert = [];

        foreach ($rows as $row) {
            $key = $row['employee_id'].'|'.$row['record_date'];
            if ($existingKeys->has($key)) {
                continue;
            }

            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $toInsert[] = $row;
        }

        if (empty($toInsert)) {
            return 0;
        }

        foreach (array_chunk($toInsert, 500) as $chunk) {
            AttendanceRecord::insert($chunk);
        }

        return count($toInsert);
    }

    /**
     * @param array<int, array{employee:Employee,ranges:array<string, array{start:Carbon,end:Carbon,label:string}>}> $queuedWfhRequests
     */
    private function queueWfhUploadRequiredNotification(array &$queuedWfhRequests, Employee $employee, Carbon $startDate, Carbon $endDate, string $leaveLabel): void
    {
        if (! isset($queuedWfhRequests[$employee->id])) {
            $queuedWfhRequests[$employee->id] = [
                'employee' => $employee,
                'ranges' => [],
            ];
        }

        $rangeKey = $startDate->toDateString().'|'.$endDate->toDateString().'|'.$leaveLabel;
        $queuedWfhRequests[$employee->id]['ranges'][$rangeKey] = [
            'start' => $startDate->copy()->startOfDay(),
            'end' => $endDate->copy()->startOfDay(),
            'label' => $leaveLabel,
        ];
    }

    /**
     * @param array<int, array{employee:Employee,ranges:array<string, array{start:Carbon,end:Carbon,label:string}>}> $queuedWfhRequests
     */
    private function sendQueuedWfhUploadRequiredNotifications(Request $request, array $queuedWfhRequests, Collection $employeeUserIdsByEmail): int
    {
        if (empty($queuedWfhRequests)) {
            return 0;
        }

        $createdNotifications = 0;

        foreach ($queuedWfhRequests as $payload) {
            $employee = $payload['employee'];
            $employeeEmail = (string) $employee->email;

            if ($employeeEmail === '' || ! $employeeUserIdsByEmail->has($employeeEmail)) {
                continue;
            }

            $ranges = collect($payload['ranges'])->values();

            if ($ranges->isEmpty()) {
                continue;
            }

            /** @var Carbon $minStart */
            $minStart = $ranges->min(fn (array $range) => $range['start']);
            /** @var Carbon $maxEnd */
            $maxEnd = $ranges->max(fn (array $range) => $range['end']);

            $existingWfhDates = WfhMonitoringSubmission::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('wfh_date', [$minStart->toDateString(), $maxEnd->toDateString()])
                ->pluck('wfh_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->flip();

            $missingDatesByLabel = [];

            foreach ($ranges as $range) {
                /** @var Carbon $start */
                $start = $range['start'];
                /** @var Carbon $end */
                $end = $range['end'];
                /** @var string $label */
                $label = $range['label'];

                foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
                    $dateKey = $date->toDateString();

                    if ($existingWfhDates->has($dateKey)) {
                        continue;
                    }

                    $missingDatesByLabel[$label][$dateKey] = true;
                }
            }

            if (empty($missingDatesByLabel)) {
                continue;
            }

            $rangeLabels = collect($missingDatesByLabel)
                ->map(function (array $dates, string $label) {
                    $dateValues = collect(array_keys($dates))
                        ->sort()
                        ->values();

                    $dateLabel = $dateValues->count() === 1
                        ? Carbon::parse($dateValues->first())->format('F d, Y')
                        : Carbon::parse($dateValues->first())->format('F d, Y')
                            .' to '.Carbon::parse($dateValues->last())->format('F d, Y');

                    return $label.' ('.$dateLabel.')';
                })
                ->values()
                ->all();

            $content = 'Your approved Work From Home leave requires WFH materials. ';
            $content .= 'Please upload your WFH monitoring sheet so HR can review and finalize your attendance as Present.';

            if (! empty($rangeLabels)) {
                $content .= ' Affected request(s): '.implode('; ', $rangeLabels).'.';
            }

            $announcement = Announcement::forceCreate([
                'title' => 'WFH Action Required',
                'content' => $content,
                'priority' => 'high',
                'target_employee_type' => 'employee',
                'published_at' => now(),
                'is_published' => true,
                'created_by' => $request->user()?->id,
            ]);

            AnnouncementNotification::create([
                'announcement_id' => $announcement->id,
                'user_id' => (int) $employeeUserIdsByEmail->get($employeeEmail),
                'is_read' => false,
                'read_at' => null,
                'redirect_url' => route('employee.wfh-monitoring.upload'),
            ]);

            $createdNotifications++;
        }

        return $createdNotifications;
    }

    /**
     * @param Collection<int, string> $byExactId
     * @param array<string, Employee> $byNormalizedId
     * @param array<string, array<int, Employee>> $byNameIndex
     */
    private function findEmployeeInMemory(
        array $record,
        Collection $byExactId,
        array $byNormalizedId,
        array $byNameIndex
    ): ?Employee {
        if (! empty($record['employee_id'])) {
            $exactId = trim($record['employee_id']);
            if ($byExactId->has($exactId)) {
                return $byExactId->get($exactId);
            }

            $normalizedId = $this->normalizeEmployeeId($exactId);
            if (isset($byNormalizedId[$normalizedId])) {
                return $byNormalizedId[$normalizedId];
            }
        }

        if (empty($record['name'])) {
            return null;
        }

        $name = trim($record['name']);

        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));
            $firstWord = trim(explode(' ', $first)[0] ?? $first);
            $lastKey = mb_strtolower($last);

            if (! isset($byNameIndex[$lastKey])) {
                return null;
            }

            foreach ($byNameIndex[$lastKey] as $emp) {
                if (str_starts_with(mb_strtolower($emp->first_name), mb_strtolower($firstWord))) {
                    return $emp;
                }
            }
        } else {
            $parts = preg_split('/\s+/', $name);
            if (count($parts) >= 2) {
                $firstName = mb_strtolower($parts[0]);
                $lastName = mb_strtolower(end($parts));

                if (isset($byNameIndex[$lastName])) {
                    foreach ($byNameIndex[$lastName] as $emp) {
                        if (mb_strtolower($emp->first_name) === $firstName) {
                            return $emp;
                        }
                    }
                }
            }
        }

        return null;
    }
}
