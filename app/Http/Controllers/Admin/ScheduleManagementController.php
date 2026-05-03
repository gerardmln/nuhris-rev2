<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeScheduleSubmission;
use App\Services\EmployeeScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleManagementController extends Controller
{
    public function index(EmployeeScheduleService $scheduleService): View
    {
        $employees = Employee::query()
            ->with('department')
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
}
