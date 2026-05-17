<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\Department;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Models\WfhMonitoringSubmission;
use App\Services\EmployeeScheduleService;
use App\Services\SupabaseStorageService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WfhMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $departmentId = $request->string('department_id')->toString() ?: 'all';
        $statusFilter = $request->string('status')->toString() ?: 'all';
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->string('date_from')->toString())->startOfDay() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->string('date_to')->toString())->endOfDay() : null;

        $submissions = WfhMonitoringSubmission::query()
            ->with(['employee.department', 'reviewer'])
            ->orderByDesc('wfh_date')
            ->orderByDesc('submitted_at')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('employee', function ($employeeQuery) use ($search): void {
                    $employeeQuery->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('employee_id', 'like', '%'.$search.'%')
                        ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($departmentId !== 'all', function ($query) use ($departmentId): void {
                if ($departmentId === 'asp') {
                    $query->whereHas('employee', function ($employeeQuery): void {
                        $employeeQuery->where('employment_type', 'Admin Support Personnel')
                            ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('name', 'ASP'));
                    });

                    return;
                }

                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            })
            ->when($statusFilter !== 'all', fn ($query) => $query->where('status', $statusFilter))
            ->when($dateFrom && $dateTo, fn ($query) => $query->whereBetween('wfh_date', [$dateFrom->toDateString(), $dateTo->toDateString()]))
            ->get();

        $mapped = $submissions->map(function (WfhMonitoringSubmission $submission): array {
            return [
                'id' => $submission->id,
                'employee_name' => $submission->employee?->full_name ?? 'Unknown employee',
                'department' => $submission->employee?->department?->name ?? '—',
                'date' => $submission->wfh_date?->format('M d, Y') ?? '—',
                'time_in' => $submission->time_in?->format('h:i A') ?? '—',
                'time_out' => $submission->time_out?->format('h:i A') ?? '—',
                'status' => $submission->status,
                'status_label' => ucfirst($submission->status),
                'status_class' => match ($submission->status) {
                    WfhMonitoringSubmission::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700',
                    WfhMonitoringSubmission::STATUS_DECLINED => 'bg-rose-100 text-rose-700',
                    default => 'bg-amber-100 text-amber-700',
                },
                'submitted_at' => $submission->submitted_at?->format('M d, Y h:i A') ?? '—',
                'reviewed_at' => $submission->reviewed_at?->format('M d, Y h:i A') ?? '—',
                'review_notes' => $submission->review_notes,
                'has_file' => filled($submission->file_path),
                'original_filename' => $submission->original_filename,
            ];
        });

        return view('hr.wfh-monitoring', [
            'submissions' => $mapped,
            'departments' => Department::query()->facultySchools()->orderBy('name')->get(),
            'stats' => [
                'all' => $mapped->count(),
                'pending' => $mapped->where('status', WfhMonitoringSubmission::STATUS_PENDING)->count(),
                'approved' => $mapped->where('status', WfhMonitoringSubmission::STATUS_APPROVED)->count(),
                'declined' => $mapped->where('status', WfhMonitoringSubmission::STATUS_DECLINED)->count(),
            ],
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'status' => $statusFilter,
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
        ]);
    }

    public function clearAll(Request $request): RedirectResponse
    {
        $deleted = WfhMonitoringSubmission::query()->delete();

        return redirect()->route('wfh-monitoring.index')->with('success', $deleted > 0
            ? 'All WFH monitoring records were cleared. Attendance records were left untouched.'
            : 'No WFH monitoring records to clear.');
    }

    public function approve(Request $request, WfhMonitoringSubmission $submission, EmployeeScheduleService $scheduleService): RedirectResponse
    {
        $validated = $request->validate([
            'confirmed' => ['accepted', 'in:1'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($submission->status === WfhMonitoringSubmission::STATUS_APPROVED) {
            return back()->with('error', 'This WFH submission is already approved.');
        }

        $submission->loadMissing('employee');

        if (! $submission->employee) {
            return back()->with('error', 'The employee profile for this submission could not be found.');
        }

        DB::transaction(function () use ($request, $submission, $scheduleService, $validated): void {
            $submission->update([
                'status' => WfhMonitoringSubmission::STATUS_APPROVED,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);

            $attendancePayload = [
                'time_in' => $submission->time_in?->format('H:i:s'),
                'time_out' => $submission->time_out?->format('H:i:s'),
                'scheduled_time_in' => $submission->time_in?->format('H:i:s'),
                'scheduled_time_out' => $submission->time_out?->format('H:i:s'),
                'tardiness_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
                'schedule_status' => 'validated',
                'schedule_notes' => 'WFH approved by HR',
                'status' => 'present',
            ];

            if ($submission->wfh_date && $submission->employee) {
                $existing = AttendanceRecord::query()->where('employee_id', $submission->employee_id)->whereDate('record_date', $submission->wfh_date)->first();

                if ($existing) {
                    $attendancePayload['scheduled_time_in'] = $submission->time_in?->format('H:i:s') ?? $existing->scheduled_time_in?->format('H:i:s');
                    $attendancePayload['scheduled_time_out'] = $submission->time_out?->format('H:i:s') ?? $existing->scheduled_time_out?->format('H:i:s');
                    $attendancePayload['time_in'] = $submission->time_in?->format('H:i:s') ?? $existing->time_in?->format('H:i:s');
                    $attendancePayload['time_out'] = $submission->time_out?->format('H:i:s') ?? $existing->time_out?->format('H:i:s');
                }
            }

            AttendanceRecord::updateOrCreate(
                [
                    'employee_id' => $submission->employee_id,
                    'record_date' => $submission->wfh_date?->toDateString(),
                ],
                $attendancePayload
            );

            $announcement = Announcement::forceCreate([
                'title' => 'WFH monitoring approved',
                'content' => sprintf('Your WFH monitoring sheet for %s was approved by HR.', $submission->wfh_date?->format('F d, Y') ?? 'your submitted date'),
                'priority' => 'medium',
                'target_user_type' => User::TYPE_EMPLOYEE,
                'published_at' => now(),
                'is_published' => true,
                'created_by' => $request->user()?->id,
            ]);

            $employeeUser = User::query()->where('email', $submission->employee->email)->first();

            if ($employeeUser) {
                AnnouncementNotification::create([
                    'announcement_id' => $announcement->id,
                    'user_id' => $employeeUser->id,
                    'is_read' => false,
                    'read_at' => null,
                    'redirect_url' => route('employee.attendance'),
                ]);
            }
        });

        return back()->with('success', 'WFH submission approved and attendance was updated.');
    }

    public function decline(Request $request, WfhMonitoringSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'confirmed' => ['accepted', 'in:1'],
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);

        if ($submission->status === WfhMonitoringSubmission::STATUS_DECLINED) {
            return back()->with('error', 'This WFH submission is already declined.');
        }

        $submission->loadMissing('employee');

        $submission->update([
            'status' => WfhMonitoringSubmission::STATUS_DECLINED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        $employeeUser = $submission->employee
            ? User::query()->where('email', $submission->employee->email)->first()
            : null;

        if ($employeeUser) {
            $announcement = Announcement::forceCreate([
                'title' => 'WFH monitoring declined',
                'content' => sprintf('Your WFH monitoring sheet for %s was declined by HR. Notes: %s', $submission->wfh_date?->format('F d, Y') ?? 'your submitted date', $validated['review_notes']),
                'priority' => 'medium',
                'target_user_type' => User::TYPE_EMPLOYEE,
                'published_at' => now(),
                'is_published' => true,
                'created_by' => $request->user()?->id,
            ]);

            AnnouncementNotification::create([
                'announcement_id' => $announcement->id,
                'user_id' => $employeeUser->id,
                'is_read' => false,
                'read_at' => null,
                'redirect_url' => route('employee.wfh-monitoring.index'),
            ]);
        }

        return back()->with('success', 'WFH submission declined. The employee has been notified.');
    }

    public function viewFile(Request $request, WfhMonitoringSubmission $submission, SupabaseStorageService $storage): RedirectResponse
    {
        if (! $submission->file_path) {
            return back()->with('error', 'No file was attached to this WFH submission.');
        }

        if (! $storage->isEnabled()) {
            return back()->with('error', 'File storage is not configured. Please contact the administrator.');
        }

        $url = $storage->createSignedUrl($submission->file_path, 300);

        if (! $url) {
            return back()->with('error', 'Unable to generate a download link. Please try again.');
        }

        return redirect()->away($url);
    }
}
