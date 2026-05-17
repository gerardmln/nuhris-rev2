<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeScheduleSubmission;
use App\Services\EmployeeScheduleService;
    use App\Models\Announcement;
    use App\Models\AnnouncementNotification;
    use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleManagementController extends Controller
{
    public function index(EmployeeScheduleService $scheduleService): View
    {
        $request = request();
        $search = $request->string('search')->trim()->toString();
        $departmentId = $request->string('department_id')->toString();
        $statusFilter = $request->string('status')->toString() ?: 'all';

        $employees = Employee::query()
            ->with('department')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($employeeQuery) use ($search): void {
                    $employeeQuery->where('first_name', 'ilike', '%'.$search.'%')
                        ->orWhere('last_name', 'ilike', '%'.$search.'%')
                        ->orWhere('email', 'ilike', '%'.$search.'%')
                        ->orWhereHas('department', function ($departmentQuery) use ($search): void {
                            $departmentQuery->where('name', 'ilike', '%'.$search.'%');
                        });
                });
            })
            ->when($departmentId !== '' && $departmentId !== 'all', function ($query) use ($departmentId): void {
                if ($departmentId === 'asp') {
                    $query->where(function ($nested): void {
                        $nested->where('employment_type', 'Admin Support Personnel')
                            ->orWhereHas('department', fn ($department) => $department->where('name', 'ASP'));
                    });

                    return;
                }

                $query->where('department_id', $departmentId);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $latestSubmissions = EmployeeScheduleSubmission::query()
            ->with(['days', 'submitter', 'reviewer', 'employee.department'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($items) => $items->first());

        $employeeSchedules = $employees->map(function (Employee $employee) use ($latestSubmissions, $scheduleService) {
            $latestSubmission = $latestSubmissions->get($employee->id);

            if (! $latestSubmission || $latestSubmission->status === EmployeeScheduleSubmission::STATUS_RESET) {
                return [
                    'employee' => $employee,
                    'submission' => null,
                    'status' => 'needs_upload',
                ];
            }

            return [
                'employee' => $employee,
                'submission' => $latestSubmission,
                'status' => $latestSubmission->status,
                'schedule_summary' => $scheduleService->summarizeSubmission($latestSubmission),
            ];
        })->when($statusFilter !== 'all', function ($collection) use ($statusFilter) {
            return $collection->filter(function (array $entry) use ($statusFilter) {
                if ($statusFilter === 'needs_upload') {
                    return $entry['status'] === 'needs_upload';
                }

                return $entry['status'] === $statusFilter;
            })->values();
        });

        $counts = [
            'pending' => EmployeeScheduleSubmission::query()->where('status', 'pending')->count(),
            'approved' => EmployeeScheduleSubmission::query()->where('status', 'approved')->count(),
            'declined' => EmployeeScheduleSubmission::query()->where('status', 'declined')->count(),
            'reset' => EmployeeScheduleSubmission::query()->where('status', EmployeeScheduleSubmission::STATUS_RESET)->count(),
            'total' => EmployeeScheduleSubmission::query()->count(),
        ];

        return view('admin.schedule-management.index', [
            'employeeSchedules' => $employeeSchedules,
            'counts' => $counts,
            'search' => $search,
            'departments' => Department::query()->orderBy('name')->get(),
            'filters' => [
                'department_id' => $departmentId,
                'status' => $statusFilter,
                'search' => $search,
            ],
        ]);
    }

    public function resetEmployee(Employee $employee): RedirectResponse
    {
        $submissions = EmployeeScheduleSubmission::query()
            ->with('employee')
            ->where('employee_id', $employee->id)
            ->get();

        if ($submissions->isEmpty()) {
            return back()->with('error', 'This employee has no schedule submissions to reset.');
        }

        foreach ($submissions as $submission) {
            $submission->days()->delete();
            $submission->update([
                'status' => EmployeeScheduleSubmission::STATUS_RESET,
                'reviewed_by' => request()->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => 'Reset by Admin',
                'is_current' => false,
            ]);
        }

        return redirect()->route('admin.schedules.index')
            ->with('success', $employee->full_name.' schedule was reset successfully.');
    }

    public function approve(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $submission->loadMissing('employee');

        $submission->update([
            'status' => EmployeeScheduleSubmission::STATUS_APPROVED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? 'Approved by Admin',
            'is_current' => true,
        ]);

        EmployeeScheduleSubmission::query()
            ->where('employee_id', $submission->employee_id)
            ->whereKeyNot($submission->id)
            ->update(['is_current' => false]);

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule for '.$submission->employee?->full_name.' has been approved.');
    }

    public function resetAll(): RedirectResponse
    {
        DB::transaction(function () {
            EmployeeScheduleSubmission::query()->update([
                'status' => EmployeeScheduleSubmission::STATUS_RESET,
                'is_current' => false,
                'reviewed_by' => request()->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => 'Reset by Admin',
            ]);

            EmployeeScheduleSubmission::query()->each(function (EmployeeScheduleSubmission $submission) {
                $submission->days()->delete();
            });
        });

        return redirect()->route('admin.schedules.index')
            ->with('success', 'All schedules were reset. Employees must resubmit before DTR validation resumes.');
    }

        public function edit(EmployeeScheduleSubmission $submission): View
        {
            $submission->loadMissing(['employee', 'days']);

            return view('admin.schedule-management.edit', [
                'submission' => $submission,
                'employee' => $submission->employee,
                'days' => $submission->days,
            ]);
        }

        public function update(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
        {
            $validated = $request->validate([
                'days' => ['required', 'array', 'size:7'],
                'days.*.day_name' => ['required', 'string'],
                'days.*.has_work' => ['required', 'boolean'],
                'days.*.time_in' => ['nullable', 'date_format:H:i'],
                'days.*.time_out' => ['nullable', 'date_format:H:i'],
            ]);

            $submission->loadMissing('employee');

            DB::transaction(function () use ($submission, $validated, $request): void {
                // Update schedule days
                $submission->days()->delete();

                foreach ($validated['days'] as $dayData) {
                    $submission->days()->create([
                        'day_name' => $dayData['day_name'],
                        'has_work' => $dayData['has_work'],
                        'time_in' => $dayData['has_work'] && $dayData['time_in'] ? $dayData['time_in'] : null,
                        'time_out' => $dayData['has_work'] && $dayData['time_out'] ? $dayData['time_out'] : null,
                    ]);
                }

                // Mark as reviewed
                $submission->update([
                    'reviewed_by' => $request->user()?->id,
                    'reviewed_at' => now(),
                    'review_notes' => 'Schedule edited by Admin',
                ]);
            });

            // Notify employee of schedule update
            $this->notifyEmployee(
                $request,
                $submission,
                'Schedule updated by Admin',
                sprintf('Your weekly schedule for %s %s was updated by the administrator. Please check your schedule.', $submission->semester_label, $submission->academic_year)
            );

            return redirect()->route('admin.schedules.index')
                ->with('success', 'Schedule for '.$submission->employee?->full_name.' has been updated. Employee has been notified.');
        }

        public function clear(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
        {
            $submission->loadMissing('employee');

            $submission->update([
                'status' => EmployeeScheduleSubmission::STATUS_RESET,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => 'Cleared by Admin',
                'is_current' => false,
            ]);

            $submission->days()->delete();

            // Notify employee
            $this->notifyEmployee(
                $request,
                $submission,
                'Schedule cleared',
                sprintf('Your weekly schedule for term "%s" was cleared by the administrator. Please resubmit a revised schedule.', $submission->semester_label)
            );

            return redirect()->route('admin.schedules.index')
                ->with('success', 'Schedule cleared successfully. Employee has been notified.');
        }

        private function notifyEmployee(Request $request, EmployeeScheduleSubmission $submission, string $title, string $content): void
        {
            $employee = $submission->employee;
            if (!$employee) {
                return;
            }

            $announcement = Announcement::forceCreate([
                'title' => $title,
                'content' => $content,
                'priority' => 'high',
                'target_user_type' => User::TYPE_EMPLOYEE,
                'published_at' => now(),
                'is_published' => true,
                'created_by' => $request->user()?->id,
            ]);

            $employeeUser = User::query()->where('email', $employee->email)->first();

            if ($employeeUser) {
                AnnouncementNotification::create([
                    'announcement_id' => $announcement->id,
                    'user_id' => $employeeUser->id,
                    'is_read' => false,
                    'read_at' => null,
                    'redirect_url' => route('employee.attendance'),
                ]);
            }
        }
}
