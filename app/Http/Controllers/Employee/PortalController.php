<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeCredentialRequest;
use App\Http\Requests\UpdateEmployeeAccountRequest;
use App\Models\AnnouncementNotification;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $employee = Employee::query()->with('department')->where('email', $user->email)->first();

        $notificationsCount = AnnouncementNotification::query()
            ->where('user_id', $user->id)
            ->count();

        $recentAlerts = AnnouncementNotification::query()
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

        return redirect()->route('employee.credentials')->with('success', 'Credential uploaded successfully. It is now pending HR review.');
    }

    public function attendance(Request $request): View
    {
        $employee = Employee::query()->where('email', $request->user()->email)->first();
        $records = $employee
            ? $employee->attendanceRecords()->orderByDesc('record_date')->get()
            : collect();

        $totals = [
            'tardiness' => $records->sum('tardiness_minutes'),
            'undertime' => $records->sum('undertime_minutes'),
            'overtime' => $records->sum('overtime_minutes'),
            'absences' => $records->where('status', 'absent')->count(),
            'workload_credits' => $records->where('status', 'present')->count(),
        ];

        $mappedRecords = $records->map(function ($record) {
            return [
                'date' => $record->record_date?->format('M d, Y') ?? '-',
                'time_in' => $record->time_in?->format('h:i A'),
                'time_out' => $record->time_out?->format('h:i A'),
                'scheduled' => ($record->scheduled_time_in?->format('h:i A') ?? '08:30 AM').' - '.($record->scheduled_time_out?->format('h:i A') ?? '05:30 PM'),
                'tardiness_minutes' => $record->tardiness_minutes,
                'undertime_minutes' => $record->undertime_minutes,
                'overtime_minutes' => $record->overtime_minutes,
                'status' => ucfirst(str_replace('_', ' ', $record->status)),
            ];
        });

        $periods = $records
            ->map(fn ($record) => optional($record->record_date)->format('F Y'))
            ->filter()
            ->unique()
            ->values();

        return view('employee.attendance', [
            'records' => $mappedRecords,
            'totals' => $totals,
            'periods' => $periods->isNotEmpty() ? $periods : collect([now()->format('F Y')]),
        ]);
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
}
