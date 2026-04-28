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
use App\Services\SupabaseStorageService;
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

        $credentialsQuery = $employee ? EmployeeCredential::query()->where('employee_id', $employee->id) : EmployeeCredential::query()->whereRaw('1 = 0');
        $activeCredentials = (clone $credentialsQuery)
            ->where('status', 'verified')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
            })
            ->count();

        $pendingCredentials = (clone $credentialsQuery)->where('status', 'pending')->count();
        $totalCredentials = (clone $credentialsQuery)->count();

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
                'compliance_passed' => $activeCredentials,
                'leave_balance' => $leaveBalance,
                'notifications' => $notificationsCount,
                'compliant' => $activeCredentials,
                'expiring_soon' => $pendingCredentials,
                'non_compliant' => max($totalCredentials - $activeCredentials - $pendingCredentials, 0),
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
            return [
                'type' => $credential->credential_type,
                'label' => match ($credential->credential_type) {
                    'resume' => 'Resume',
                    'prc' => 'PRC License',
                    'seminars' => 'Seminars',
                    'degrees' => 'Degrees',
                    default => 'Ranking',
                },
                'title' => $credential->title,
                'status' => ucfirst(str_replace('_', ' ', $credential->status)),
                'status_raw' => $credential->status,
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
            'credentialTypes' => [
                'Resume',
                'PRC License',
                'Seminar / Training',
                'Academic Degree',
                'Ranking File',
            ],
            'departments' => Department::query()->schools()->orderBy('name')->get(),
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
            try {
                $filePath = $storage->uploadFile($request->file('credential_file'), 'employee-'.$employee->id);
                $originalFilename = $request->file('credential_file')->getClientOriginalName();
            } catch (\Throwable $e) {
                return back()
                    ->withInput()
                    ->with('error', 'File upload failed: '.$e->getMessage());
            }
        }

        DB::transaction(function () use ($request, $employee, $filePath, $originalFilename): void {
            EmployeeCredential::create([
                'employee_id' => $employee->id,
                'credential_type' => $request->string('credential_type')->toString(),
                'title' => $request->string('title')->toString(),
                'department_id' => $request->input('department_id'),
                'expires_at' => $request->input('expires_at'),
                'description' => $request->input('description'),
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'status' => 'pending',
            ]);

            $hrAnnouncement = Announcement::forceCreate([
                'title' => 'New credential uploaded',
                'content' => sprintf(
                    '%s uploaded a %s credential titled "%s" and it is awaiting HR review.',
                    $employee->full_name,
                    $request->string('credential_type')->toString(),
                    $request->string('title')->toString()
                ),
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

    public function attendance(Request $request, EmployeeScheduleService $scheduleService): View
    {
        $employee = Employee::query()->where('email', $request->user()->email)->first();
        $records = $employee
            ? $employee->attendanceRecords()->orderByDesc('record_date')->get()
            : collect();
        $currentSchedule = $employee ? $scheduleService->currentSubmission($employee) : null;
        $canEditSchedule = ! ($currentSchedule && in_array($currentSchedule->status, [EmployeeScheduleSubmission::STATUS_PENDING, EmployeeScheduleSubmission::STATUS_APPROVED], true));

        $totals = [
            'tardiness' => $records->sum('tardiness_minutes'),
            'undertime' => $records->sum('undertime_minutes'),
            'overtime' => $records->sum('overtime_minutes'),
            'absences' => $records->filter(fn ($record) => $record->status === 'absent' && ! in_array($record->schedule_status, ['non_working_day', 'no_schedule'], true))->count(),
            'workload_credits' => $records->filter(fn ($record) => $record->status === 'present' && $record->schedule_status === 'validated')->count(),
        ];

        $overallResult = $this->buildAttendanceResult($totals, $currentSchedule, $records->isNotEmpty());

        $mappedRecords = $records->map(function ($record) {
            $statusLabel = match ($record->schedule_status) {
                'no_schedule' => 'No schedule available',
                'non_working_day' => 'Non-working day',
                default => ucfirst(str_replace('_', ' ', $record->status)),
            };

            return [
                'date' => $record->record_date?->format('M d, Y') ?? '-',
                'time_in' => $record->time_in?->format('h:i A'),
                'time_out' => $record->time_out?->format('h:i A'),
                'scheduled' => $record->schedule_status === 'validated'
                    ? (($record->scheduled_time_in?->format('h:i A') ?? 'N/A').' - '.($record->scheduled_time_out?->format('h:i A') ?? 'N/A'))
                    : ucfirst(str_replace('_', ' ', (string) $record->schedule_status)),
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

    public function leave(Request $request): View
    {
        $employee = Employee::query()->where('email', $request->user()->email)->first();

        $leaveBalances = $employee
            ? $employee->leaveBalances()->orderBy('leave_type')->get()->map(fn ($row) => [
                'type' => $row->leave_type,
                'remaining' => rtrim(rtrim(number_format($row->remaining_days, 2, '.', ''), '0'), '.'),
            ])->values()
            : collect();

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

        return view('employee.leave', [
            'leaveBalances' => $leaveBalances,
            'leaveHistory' => $leaveHistory,
        ]);
    }

    public function account(Request $request): View
    {
        $employee = Employee::query()->with('department')->where('email', $request->user()->email)->first();

        return view('employee.account', [
            'employee' => $employee,
            'departments' => Department::query()->schools()->orderBy('name')->get(),
            'employeeTypes' => ['Faculty', 'Security', 'ASP'],
        ]);
    }

    public function updateAccount(UpdateEmployeeAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $employee = Employee::query()->where('email', $user->email)->first();

        $user->update([
            'name' => $request->string('name')->toString(),
        ]);

        if ($employee) {
            $departmentId = $request->input('department_id') ?: $employee->department_id;

            $employee->update([
                'employee_id' => $request->input('employee_id') ?: $employee->employee_id,
                'department_id' => $departmentId,
                'phone' => $request->input('phone'),
                'position' => $request->input('position'),
                'hire_date' => $request->input('hire_date'),
                'address' => $request->input('address'),
                'employment_type' => $request->input('employee_type') ?: $employee->employment_type,
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
}
