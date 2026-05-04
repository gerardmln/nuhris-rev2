<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeCredentialRequest;
use App\Http\Requests\UpdateEmployeeAccountRequest;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\EmployeeScheduleSubmission;
use App\Models\User;
use App\Services\EmployeeScheduleService;
use App\Services\LeaveBalanceService;
use App\Services\SupabaseStorageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalController extends Controller
{
    private const ALLOWED_TERM_LABELS = [
        '1st Term',
        '2nd Term',
        '3rd Term',
    ];

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $employee = Employee::query()->with('department')->where('email', $user->email)->first();

        $notificationsCount = AnnouncementNotification::query()
            ->visible()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $recentAlerts = AnnouncementNotification::query()
            ->visible()
            ->with('announcement')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(3)
            ->get();

        $credentials = $employee
            ? EmployeeCredential::query()->where('employee_id', $employee->id)->get()
            : collect();

        $verifiedCredentials = $credentials->where('status', 'verified')->values();
        $expiringSoonCredentials = $verifiedCredentials->filter(fn (EmployeeCredential $credential) => $credential->isExpiringSoon())->values();
        $compliantCredentials = $verifiedCredentials->filter(function (EmployeeCredential $credential) use ($expiringSoonCredentials) {
            return ! $credential->isExpiringSoon() && (! $credential->expires_at || $credential->expires_at >= now()->startOfDay());
        })->values();

        $activeCredentials = $verifiedCredentials->count();
        $pendingCredentials = $credentials->where('status', 'pending')->count();
        $totalCredentials = $credentials->count();
        $expiringSoonCount = $expiringSoonCredentials->count();
        $compliantCount = $compliantCredentials->count();

        $calendarEvents = $recentAlerts
            ->map(fn (AnnouncementNotification $notification) => $notification->announcement?->title)
            ->filter()
            ->values();

        $leaveBalance = $employee
            ? (float) $employee->leaveBalances()->sum('remaining_days')
            : 0;

        return view('employee.dashboard', [
            'employee' => $employee,
            'stats' => [
                'active_credentials' => $activeCredentials,
                'pending_credentials' => $pendingCredentials,
                'compliance_total' => max($totalCredentials, 1),
                'compliance_passed' => $compliantCount + $expiringSoonCount,
                'leave_balance' => $leaveBalance,
                'notifications' => $notificationsCount,
                'compliant' => $compliantCount,
                'expiring_soon' => $expiringSoonCount,
                'non_compliant' => max($totalCredentials - $compliantCount - $expiringSoonCount, 0),
            ],
            'recentAlerts' => $recentAlerts,
            'calendar' => [
                'month_label' => now()->format('F Y'),
                'today' => (int) now()->format('j'),
                'events' => $calendarEvents,
            ],
        ]);
    }

    public function credentials(Request $request): View
    {
        $employee = Employee::query()->with('department')->where('email', $request->user()->email)->first();

        $credentials = $employee
            ? EmployeeCredential::query()->where('employee_id', $employee->id)->latest()->get()
            : collect();

        $mapped = $credentials->map(function (EmployeeCredential $credential) {
            $resumeYear = (int) ($credential->created_at?->year ?? now()->year);
            $resumeAcademicYearTitle = sprintf('Resume A.Y. %d-%d', $resumeYear, $resumeYear + 1);

            return [
                'id' => $credential->id,
                'type' => $credential->credential_type,
                'label' => match ($credential->credential_type) {
                    'resume' => 'Resume',
                    'prc' => 'PRC License',
                    'seminars' => 'Seminars',
                    'degrees' => 'Degrees',
                    default => 'Ranking',
                },
                'title' => $credential->credential_type === 'resume'
                    ? ((blank($credential->title) || $credential->title === 'Resume') ? $resumeAcademicYearTitle : $credential->title)
                    : ($credential->title ?: match ($credential->credential_type) {
                        'prc' => 'PRC License',
                        'seminars' => 'Seminar / Training',
                        'degrees' => 'Academic Degree',
                        default => 'Ranking File',
                    }),
                'status' => ucfirst(str_replace('_', ' ', $credential->status)),
                'status_raw' => $credential->status,
                'is_expired' => $credential->isExpired(),
                'is_expiring_soon' => $credential->status === 'verified' && $credential->isExpiringSoon(),
                'expires_at' => $credential->effectiveExpiresAt()?->format('M d, Y'),
                'has_file' => filled($credential->file_path),
                'original_filename' => $credential->original_filename,
                'review_notes' => $credential->review_notes,
                'reviewed_at' => $credential->reviewed_at?->format('M d, Y'),
                'updated_at' => $credential->updated_at,
            ];
        });

        $byType = $mapped->groupBy('type')->map->count();

        return view('employee.credentials', [
            'credentials' => $mapped,
            'credentialCounts' => [
                'all' => $mapped->count(),
                'resume' => (int) $byType->get('resume', 0),
                'prc' => (int) $byType->get('prc', 0),
                'seminars' => (int) $byType->get('seminars', 0),
                'degrees' => (int) $byType->get('degrees', 0),
                'ranking' => (int) $byType->get('ranking', 0),
            ],
        ]);
    }

    public function credentialsUpload(Request $request): View
    {
        $employee = Employee::query()->with('department')->where('email', $request->user()->email)->first();

        return view('employee.credentials-upload', [
            'employee' => $employee,
        ]);
    }

    public function storeCredential(StoreEmployeeCredentialRequest $request, SupabaseStorageService $storage): RedirectResponse
    {
        $employee = Employee::query()->where('email', $request->user()->email)->first();

        if (! $employee) {
            return redirect()->route('employee.credentials')->with('error', 'Employee profile not found. Please contact HR.');
        }

        $filePath = null;
        $originalFilename = null;

        if ($request->hasFile('credential_file')) {
            if (! $storage->isEnabled()) {
                return back()->withInput()->with('error', 'File storage is not configured. Please contact the administrator.');
            }

            try {
                $filePath = $storage->uploadFile($request->file('credential_file'), 'employee-'.$employee->id);
                $originalFilename = $request->file('credential_file')->getClientOriginalName();
            } catch (\Throwable $e) {
                return back()
                    ->withInput()
                    ->with('error', 'File upload failed: '.$e->getMessage());
            }
        }

        $credentialType = $request->string('credential_type')->toString();
        $expiresAt = match ($credentialType) {
            'resume' => now()->addYear()->toDateString(),
            'prc' => $request->input('expires_at'),
            default => null,
        };
        $credentialTypeLabel = match ($credentialType) {
            'resume' => 'Resume',
            'prc' => 'PRC License',
            'seminars' => 'Seminar / Training',
            'degrees' => 'Academic Degree',
            default => 'Ranking File',
        };
        $submissionYear = (int) now()->year;
        $resumeAcademicYearTitle = sprintf('Resume A.Y. %d-%d', $submissionYear, $submissionYear + 1);
        $title = in_array($credentialType, ['seminars', 'degrees'], true)
            ? trim($request->string('title')->toString())
            : ($credentialType === 'resume' ? $resumeAcademicYearTitle : $credentialTypeLabel);

        DB::transaction(function () use ($request, $employee, $filePath, $originalFilename, $credentialType, $credentialTypeLabel, $title, $expiresAt): void {
            $credential = EmployeeCredential::create([
                'employee_id' => $employee->id,
                'credential_type' => $credentialType,
                'title' => $title,
                'department_id' => $employee->department_id,
                'expires_at' => $expiresAt,
                'description' => $request->input('description'),
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'status' => 'pending',
            ]);

            if ($credentialType === 'resume') {
                $employee->forceFill([
                    'resume_last_updated_at' => now()->toDateString(),
                ])->save();
            }

            $announcementContent = $title
                ? sprintf(
                    '%s uploaded a %s credential titled "%s" and it is awaiting HR review.',
                    $employee->full_name,
                    $credentialTypeLabel,
                    $title
                )
                : sprintf(
                    '%s uploaded a %s credential and it is awaiting HR review.',
                    $employee->full_name,
                    $credentialTypeLabel
                );

            $hrAnnouncement = Announcement::forceCreate([
                'title' => 'New credential uploaded',
                'content' => $announcementContent,
                'priority' => 'medium',
                'target_user_type' => User::TYPE_HR,
                'published_at' => now(),
                'is_published' => true,
                'created_by' => $request->user()->id,
            ]);

            $hrUserIds = User::query()
                ->where('user_type', User::TYPE_HR)
                ->pluck('id');

            $rows = $hrUserIds->map(fn ($userId) => [
                'announcement_id' => $hrAnnouncement->id,
                'user_id' => $userId,
                'is_read' => false,
                'read_at' => null,
                'redirect_url' => route('credentials.index'),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if (! empty($rows)) {
                AnnouncementNotification::insert($rows);
            }
        });

        return redirect()->route('employee.credentials')->with('success', 'Credential uploaded successfully. It is now pending HR review.');
    }

    public function viewCredentialFile(Request $request, EmployeeCredential $credential, SupabaseStorageService $storage): RedirectResponse
    {
        $employee = Employee::query()->where('email', $request->user()->email)->firstOrFail();

        abort_unless($credential->employee_id === $employee->id, 403);

        if (! $credential->file_path) {
            return back()->with('error', 'No file was attached to this credential.');
        }

        if (! $storage->isEnabled()) {
            return back()->with('error', 'File storage is not configured. Please contact the administrator.');
        }

        $url = $storage->createSignedUrl($credential->file_path, 300);

        if (! $url) {
            return back()->with('error', 'Unable to generate a download link. Please try again.');
        }

        return redirect()->away($url);
    }

    public function destroyCredential(Request $request, EmployeeCredential $credential, SupabaseStorageService $storage): RedirectResponse
    {
        $employee = Employee::query()->where('email', $request->user()->email)->firstOrFail();

        abort_unless($credential->employee_id === $employee->id, 403);

        $credentialType = $credential->credential_type;
        $filePath = $credential->file_path;

        DB::transaction(function () use ($employee, $credential, $credentialType): void {
            $credential->delete();

            if ($credentialType === 'resume') {
                $latestResume = EmployeeCredential::query()
                    ->where('employee_id', $employee->id)
                    ->where('credential_type', 'resume')
                    ->where('status', 'verified')
                    ->latest('updated_at')
                    ->first();

                $employee->forceFill([
                    'resume_last_updated_at' => $latestResume?->updated_at?->toDateString(),
                ])->save();
            }
        });

        if ($filePath && $storage->isEnabled()) {
            $storage->delete($filePath);
        }

        return redirect()->route('employee.credentials')->with('success', 'Credential deleted successfully.');
    }

    public function attendance(Request $request, EmployeeScheduleService $scheduleService): View
    {
        $employee = Employee::query()->where('email', $request->user()->email)->first();
        $systemStart = Carbon::create(2026, 4, 1)->startOfDay();
        $selectedDate = Carbon::createFromDate(
            $request->integer('year', now()->year),
            $request->integer('month', now()->month),
            1
        );

        $periodStart = $selectedDate->copy()->startOfMonth()->max($systemStart);
        $periodEnd = $selectedDate->isSameMonth(now())
            ? now()->copy()->endOfDay()
            : ($selectedDate->isFuture() ? $selectedDate->copy()->startOfMonth()->subDay()->endOfDay() : $selectedDate->copy()->endOfMonth());

        $records = $employee && $periodEnd->gte($periodStart)
            ? $employee->attendanceRecords()
                ->whereBetween('record_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orderByDesc('record_date')
                ->get()
            : collect();
        $currentSchedule = $employee ? $scheduleService->currentSubmission($employee) : null;
        $canEditSchedule = ! ($currentSchedule && in_array($currentSchedule->status, [EmployeeScheduleSubmission::STATUS_PENDING, EmployeeScheduleSubmission::STATUS_APPROVED], true));

        $totals = [
            'tardiness' => $records->sum('tardiness_minutes'),
            'undertime' => $records->sum('undertime_minutes'),
            'overtime' => $records->sum('overtime_minutes'),
            'absences' => $employee
                ? $scheduleService->countDtrAbsences($employee, $periodStart->copy()->startOfDay(), $periodEnd->copy()->startOfDay())
                : 0,
            'workload_credits' => $records->filter(fn ($record) => $record->status === 'present' && $record->schedule_status === 'validated')->count(),
        ];

        $overallResult = $this->buildAttendanceResult($totals, $currentSchedule, $records->isNotEmpty());

        $mappedRecords = $records->map(function ($record) use ($scheduleService, $employee) {
            $statusLabel = match ($record->schedule_status) {
                'no_schedule' => 'No schedule available',
                'non_working_day' => 'Non-working day',
                default => ucfirst(str_replace('_', ' ', $record->status)),
            };

            $scheduledIn = null;
            $scheduledOut = null;

            if ($record->scheduled_time_in && $record->scheduled_time_out) {
                $scheduledIn = $record->scheduled_time_in?->format('h:i A');
                $scheduledOut = $record->scheduled_time_out?->format('h:i A');
            } else {
                // Derive scheduled times from the employee's approved schedule for that date
                try {
                    $evaluation = $scheduleService->evaluateDailyRecord($employee, $record->record_date, $record->time_in?->format('H:i') ?? null, $record->time_out?->format('H:i') ?? null);
                    if (! in_array($evaluation['schedule_status'], ['no_schedule', 'non_working_day'], true)) {
                        if (! empty($evaluation['scheduled_time_in'])) {
                            $scheduledIn = \Carbon\Carbon::createFromFormat('H:i:s', $evaluation['scheduled_time_in'])->format('h:i A');
                        }
                        if (! empty($evaluation['scheduled_time_out'])) {
                            $scheduledOut = \Carbon\Carbon::createFromFormat('H:i:s', $evaluation['scheduled_time_out'])->format('h:i A');
                        }
                    }
                } catch (\Throwable $e) {
                    // fallback: leave scheduled times null
                }
            }

            $scheduledLabel = ($scheduledIn || $scheduledOut)
                ? (($scheduledIn ?? 'N/A').' - '.($scheduledOut ?? 'N/A'))
                : ucfirst(str_replace('_', ' ', (string) $record->schedule_status));

            // Format actual times (time columns may be strings or DateTime instances)
            $formatTime = function ($value) {
                if (! $value) {
                    return null;
                }

                if (is_string($value)) {
                    // Try common time formats
                    try {
                        return \Carbon\Carbon::createFromFormat('H:i:s', $value)->format('h:i A');
                    } catch (\Throwable $e) {
                        try {
                            return \Carbon\Carbon::parse($value)->format('h:i A');
                        } catch (\Throwable $_) {
                            return (string) $value;
                        }
                    }
                }

                try {
                    return $value->format('h:i A');
                } catch (\Throwable $_) {
                    return null;
                }
            };

            return [
                'date' => $record->record_date?->format('M d, Y') ?? '-',
                'time_in' => $formatTime($record->time_in),
                'time_out' => $formatTime($record->time_out),
                'scheduled' => $scheduledLabel,
                'tardiness_minutes' => $record->tardiness_minutes,
                'undertime_minutes' => $record->undertime_minutes,
                'overtime_minutes' => $record->overtime_minutes,
                'schedule_status' => $record->schedule_status,
                'schedule_notes' => $record->schedule_notes,
                'status' => $statusLabel,
            ];
        });

        $scheduleDays = $currentSchedule?->days->keyBy('day_name') ?? collect();

        return view('employee.attendance', [
            'records' => $mappedRecords,
            'totals' => $totals,
            'scheduleDays' => $scheduleService->weeklyDays(),
            'currentSchedule' => $currentSchedule,
            'scheduleDayMap' => $scheduleDays,
            'canEditSchedule' => $canEditSchedule,
            'overallResult' => $overallResult,
            'selectedMonth' => $selectedDate->month,
            'selectedYear' => $selectedDate->year,
        ]);
    }

    public function storeSchedule(Request $request, EmployeeScheduleService $scheduleService): RedirectResponse
    {
        $employee = Employee::query()->where('email', $request->user()->email)->firstOrFail();
        $existingSchedule = $scheduleService->currentSubmission($employee);

        if ($existingSchedule && in_array($existingSchedule->status, [EmployeeScheduleSubmission::STATUS_PENDING, EmployeeScheduleSubmission::STATUS_APPROVED], true)) {
            return back()->with('error', 'You already have an active schedule submission. Please wait for HR review or request a reset before submitting again.');
        }

        $rules = [];
        foreach ($scheduleService->weeklyDays() as $day) {
            $rules["days.{$day['key']}.mode"] = ['required', 'in:with_work,no_work'];
            $rules["days.{$day['key']}.time_in"] = ['nullable', 'date_format:H:i'];
            $rules["days.{$day['key']}.time_out"] = ['nullable', 'date_format:H:i'];
        }

        $rules['term_label'] = ['required', 'string', 'in:'.implode(',', self::ALLOWED_TERM_LABELS)];

        $validated = $request->validate($rules);
        $scheduleRows = $scheduleService->normalizeWeeklyInput($validated['days'] ?? []);

        DB::transaction(function () use ($employee, $request, $scheduleRows, $validated, $existingSchedule): void {
            $submission = EmployeeScheduleSubmission::query()->firstOrNew([
                'employee_id' => $employee->id,
                'semester_label' => $validated['term_label'],
            ]);

            $submission->fill([
                'term_label' => $validated['term_label'],
                'submitted_by' => $request->user()->id,
                'submitted_at' => now(),
                'academic_year' => null,
                'status' => EmployeeScheduleSubmission::STATUS_PENDING,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
                'is_current' => false,
            ]);
            $submission->save();

            $submission->days()->delete();

            foreach ($scheduleRows as $row) {
                $submission->days()->create($row);
            }

            $announcement = Announcement::forceCreate([
                'title' => sprintf('%s submitted a weekly schedule', $employee->full_name),
                'content' => sprintf('%s submitted a weekly schedule for term "%s" and is waiting for HR approval.', $employee->full_name, $validated['term_label']),
                'priority' => 'medium',
                'target_employee_type' => 'hr',
                'published_at' => now(),
                'is_published' => true,
                'created_by' => $request->user()->id,
            ]);

            $hrUserIds = User::query()->where('user_type', User::TYPE_HR)->pluck('id');

            $rows = $hrUserIds->map(fn ($userId) => [
                'announcement_id' => $announcement->id,
                'user_id' => $userId,
                'is_read' => false,
                'read_at' => null,
                'redirect_url' => route('schedules.index'),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if ($rows) {
                AnnouncementNotification::insert($rows);
            }
        });

        return back()->with('success', 'Your weekly schedule was submitted to HR for approval.');
    }

    public function leave(Request $request, LeaveBalanceService $leaveBalanceService): View
    {
        $employee = Employee::query()->where('email', $request->user()->email)->first();

        // Get deductible leave balances (VL, SL, EL only)
        $deductibleBalances = $employee
            ? $leaveBalanceService->getDeductibleLeaveBalances($employee)
                ->map(fn ($balance) => [
                    'type' => $balance['type'],
                    'remaining' => rtrim(rtrim(number_format($balance['remaining'], 2, '.', ''), '0'), '.'),
                ])->values()
            : collect();

        // Get all leave history
        $leaveHistory = $employee
            ? $employee->leaveRequests()->latest('start_date')->get()->map(fn ($row) => [
                'type' => $row->leave_type,
                'start' => $row->start_date?->toDateString(),
                'end' => $row->end_date?->toDateString(),
                'days' => rtrim(rtrim(number_format($row->days_deducted, 2, '.', ''), '0'), '.'),
                'status' => ucfirst($row->status),
                'cutoff' => $row->cutoff_date?->format('M d, Y') ?? '-',
                'reason' => $row->reason,
            ])->values()
            : collect();

        // Get detailed leave usage breakdown (deductible vs tracked-only)
        $leaveUsageBreakdown = $employee
            ? $leaveBalanceService->getLeaveUsageBreakdown($employee)
            : [
                'deductible' => [],
                'tracked_only' => [],
            ];

        // For backward compatibility, also provide simple usage counts
        $leaveUsage = [
            'vacation_used' => 0,
            'sick_used' => 0,
            'emergency_used' => 0,
        ];

        if ($employee) {
            foreach ($leaveUsageBreakdown['deductible'] as $usage) {
                if (str_contains(strtolower($usage['type']), 'vacation')) {
                    $leaveUsage['vacation_used'] += $usage['days_used'];
                } elseif (str_contains(strtolower($usage['type']), 'sick')) {
                    $leaveUsage['sick_used'] += $usage['days_used'];
                } elseif (str_contains(strtolower($usage['type']), 'emergency')) {
                    $leaveUsage['emergency_used'] += $usage['days_used'];
                }
            }
        }

        return view('employee.leave', [
            'leaveBalances' => $deductibleBalances,
            'leaveHistory' => $leaveHistory,
            'leaveUsage' => $leaveUsage,
            'leaveUsageBreakdown' => $leaveUsageBreakdown,
            'employee' => $employee,
        ]);
    }

    public function account(Request $request): View
    {
        $employee = Employee::query()->with('department')->where('email', $request->user()->email)->first();

        $phoneValue = $employee?->phone;
        $phoneValue = is_string($phoneValue) ? preg_replace('/^\+?63/', '', trim($phoneValue)) : null;
        $phoneValue = is_string($phoneValue) ? ltrim($phoneValue, '0') : null;

        return view('employee.account', [
            'employee' => $employee,
            'departments' => Department::query()->schools()->orderBy('name')->get(),
            'employeeTypes' => ['Faculty', 'ASP'],
            'phoneValue' => $phoneValue,
        ]);
    }

    public function updateAccount(UpdateEmployeeAccountRequest $request): RedirectResponse
    {
        $employee = Employee::query()->where('email', $request->user()->email)->first();

        if ($employee) {
            $employee->update([
                'phone' => $this->normalizePhilippinePhone($request->input('phone')),
                'address' => $request->input('address'),
            ]);
        }

        return redirect()->route('employee.account')->with('success', 'Account updated successfully.');
    }

    /**
     * Change the employee's password.
     * Requires current (old) password + new password + confirmation.
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'new_password.required' => 'Please enter a new password.',
            'new_password.min' => 'New password must be at least 6 characters.',
            'new_password.confirmed' => 'New password and confirmation do not match.',
        ]);

        $user = $request->user();

        if (! \Illuminate\Support\Facades\Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password you entered is incorrect.',
            ])->with('password_error', true);
        }

        $user->forceFill([
            'password' => \Illuminate\Support\Facades\Hash::make($request->input('new_password')),
        ])->save();

        return redirect()->route('employee.account')->with('password_success', 'Password changed successfully.');
    }

    private function buildAttendanceResult(array $totals, ?EmployeeScheduleSubmission $currentSchedule, bool $hasRecords): array
    {
        if (! $hasRecords) {
            return [
                'label' => 'No DTR yet',
                'description' => 'Attendance records have not been uploaded yet.',
                'variant' => 'neutral',
            ];
        }

        if ($currentSchedule && $currentSchedule->status === EmployeeScheduleSubmission::STATUS_PENDING) {
            return [
                'label' => 'For approval',
                'description' => 'Your schedule is waiting for HR approval.',
                'variant' => 'warning',
            ];
        }

        if (($totals['absences'] ?? 0) > 0) {
            return [
                'label' => 'Needs attention',
                'description' => 'Your DTR has absences that need review.',
                'variant' => 'danger',
            ];
        }

        if (($totals['tardiness'] ?? 0) > 0 || ($totals['undertime'] ?? 0) > 0) {
            return [
                'label' => 'With deductions',
                'description' => 'Your DTR has tardiness or undertime entries.',
                'variant' => 'warning',
            ];
        }

        return [
            'label' => 'Good standing',
            'description' => 'Your DTR is clear based on the current records.',
            'variant' => 'success',
        ];
    }

    private function normalizePhilippinePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, '63')) {
            return '+'.$phone;
        }

        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return '+63'.$phone;
    }
}
