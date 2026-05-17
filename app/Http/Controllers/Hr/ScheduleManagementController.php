<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeScheduleSubmission;
use App\Models\User;
use App\Services\EmployeeScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                    $employeeQuery->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('department', function ($departmentQuery) use ($search): void {
                            $departmentQuery->where('name', 'like', '%'.$search.'%');
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
            ->with(['days', 'submitter', 'reviewer'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($items) => $items->first());

        $employeeSchedules = $employees->map(function (Employee $employee) use ($latestSubmissions) {
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
            ];
        })->when($statusFilter !== 'all', function ($collection) use ($statusFilter) {
            return $collection->filter(function (array $entry) use ($statusFilter) {
                if ($statusFilter === 'needs_upload') {
                    return $entry['status'] === 'needs_upload';
                }

                return $entry['status'] === $statusFilter;
            })->values();
        });

        return view('hr.schedule-management', [
            'employeeSchedules' => $employeeSchedules,
            'departments' => Department::query()->facultySchools()->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function approve(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'confirmed' => ['accepted', 'in:1'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $submission->loadMissing('employee');

        $submission->update([
            'status' => EmployeeScheduleSubmission::STATUS_APPROVED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
            'is_current' => true,
        ]);

        EmployeeScheduleSubmission::query()
            ->where('employee_id', $submission->employee_id)
            ->whereKeyNot($submission->id)
            ->update(['is_current' => false]);

        $this->notifyEmployee(
            $request,
            $submission,
            'Schedule approved',
            sprintf('Your weekly schedule for %s %s was approved by HR.', $submission->semester_label, $submission->academic_year)
        );

        return back()->with('success', 'Schedule approved successfully.');
    }

    public function clear(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'confirmed' => ['accepted', 'in:1'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($submission->status !== EmployeeScheduleSubmission::STATUS_APPROVED) {
            return back()->with('error', 'Only approved schedules can be cleared.');
        }

        $submission->loadMissing('employee');

        $submission->update([
            'status' => EmployeeScheduleSubmission::STATUS_RESET,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? 'Cleared by HR',
            'is_current' => false,
        ]);

        $submission->days()->delete();

        $this->notifyEmployee(
            $request,
            $submission,
            'Schedule cleared',
            sprintf('Your approved weekly schedule for term "%s" was cleared by HR. Please resubmit a revised schedule.', $submission->semester_label)
        );

        return back()->with('success', 'Approved schedule cleared successfully.');
    }

    public function resetEmployee(Request $request, Employee $employee): RedirectResponse
    {
        $request->validate([
            'confirmed' => ['accepted', 'in:1'],
        ]);

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
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => 'Reset by HR',
                'is_current' => false,
            ]);
        }

        $this->notifyEmployee(
            $request,
            $submissions->first(),
            'Schedule reset',
            sprintf('%s schedule has been reset by HR. Please submit a new schedule.', $employee->full_name)
        );

        return back()->with('success', $employee->full_name.' schedule was reset successfully.');
    }

    public function decline(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'confirmed' => ['accepted', 'in:1'],
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);

        $submission->loadMissing('employee');

        $submission->update([
            'status' => EmployeeScheduleSubmission::STATUS_DECLINED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
            'is_current' => false,
        ]);

        $this->notifyEmployee(
            $request,
            $submission,
            'Schedule declined',
            sprintf('Your weekly schedule for %s %s was declined by HR. Notes: %s', $submission->semester_label, $submission->academic_year, $validated['review_notes'])
        );

        return back()->with('success', 'Schedule declined. The employee has been notified.');
    }

    public function resetSemester(Request $request, EmployeeScheduleService $scheduleService): RedirectResponse
    {
        $request->validate([
            'confirmed' => ['accepted', 'in:1'],
        ]);

        EmployeeScheduleSubmission::query()->update([
            'status' => EmployeeScheduleSubmission::STATUS_RESET,
            'is_current' => false,
        ]);

        return back()->with('success', 'All schedules were reset. Employees must resubmit before DTR validation resumes.');
    }

    private function notifyEmployee(Request $request, EmployeeScheduleSubmission $submission, string $title, string $content): void
    {
        if (! $submission->employee) {
            return;
        }

        $employeeUser = User::query()->where('email', $submission->employee->email)->first();

        if (! $employeeUser) {
            return;
        }

        $announcement = Announcement::forceCreate([
            'title' => $title,
            'content' => $content,
            'priority' => 'medium',
            'target_employee_type' => 'employee',
            'published_at' => now(),
            'is_published' => true,
            'created_by' => $request->user()->id,
        ]);

        AnnouncementNotification::create([
            'announcement_id' => $announcement->id,
            'user_id' => $employeeUser->id,
            'is_read' => false,
            'read_at' => null,
            'redirect_url' => route('employee.attendance'),
        ]);
    }
}