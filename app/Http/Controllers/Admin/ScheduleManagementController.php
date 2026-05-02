<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeScheduleSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleManagementController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->string('status')->toString() ?: 'all';

        $query = EmployeeScheduleSubmission::query()
            ->with('employee')
            ->latest('submitted_at');

        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'approved', 'declined'], true)) {
            $query->where('status', $statusFilter);
        }

        $submissions = $query->get();

        $counts = [
            'pending' => EmployeeScheduleSubmission::query()->where('status', 'pending')->count(),
            'approved' => EmployeeScheduleSubmission::query()->where('status', 'approved')->count(),
            'declined' => EmployeeScheduleSubmission::query()->where('status', 'declined')->count(),
            'total' => EmployeeScheduleSubmission::query()->count(),
        ];

        return view('admin.schedule-management.index', [
            'submissions' => $submissions,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function approve(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
    {
        $submission->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->string('review_notes')->toString() ?: null,
        ]);

        return redirect()->route('admin.schedules.index')
            ->with('success', "Schedule for {$submission->employee?->full_name} has been approved.");
    }

    public function decline(Request $request, EmployeeScheduleSubmission $submission): RedirectResponse
    {
        $submission->update([
            'status' => 'declined',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->string('review_notes')->toString() ?: null,
        ]);

        return redirect()->route('admin.schedules.index')
            ->with('success', "Schedule for {$submission->employee?->full_name} has been declined.");
    }

    public function resetEmployee(Employee $employee): RedirectResponse
    {
        $count = EmployeeScheduleSubmission::query()
            ->where('employee_id', $employee->id)
            ->forceDelete();

        return redirect()->route('admin.schedules.index')
            ->with('success', "Reset {$count} schedule submission(s) for {$employee->full_name}.");
    }

    public function resetAll(): RedirectResponse
    {
        $count = EmployeeScheduleSubmission::query()->count();

        DB::transaction(function () {
            EmployeeScheduleSubmission::query()->forceDelete();
        });

        return redirect()->route('admin.schedules.index')
            ->with('success', "All {$count} schedule submissions have been reset.");
    }
}
