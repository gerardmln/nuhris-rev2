<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WfhMonitoringSubmission;
use App\Services\EmployeeScheduleService;
use App\Services\SupabaseStorageService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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

        $employees = Employee::query()->orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.dtr.index', [
            'records' => $records,
            'employee' => $employee,
            'employees' => $employees,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function editDtrRecord(AttendanceRecord $record): View
    {
        return view('admin.dtr.edit', [
            'record' => $record,
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

    public function leaveIndex(): View
    {
        $requests = LeaveRequest::query()
            ->with('employee')
            ->latest('created_at')
            ->get();

        $balances = LeaveBalance::query()
            ->with('employee')
            ->get();

        return view('admin.leave-management.index', [
            'requests' => $requests,
            'balances' => $balances,
        ]);
    }

    public function clearAllLeaves(): RedirectResponse
    {
        DB::transaction(function () {
            LeaveRequest::query()->forceDelete();
            LeaveBalance::query()->forceDelete();
        });

        return redirect()->route('admin.leave-management.index')
            ->with('success', 'All leave requests and balances have been cleared.');
    }

    public function resetEmployeeLeaves(Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($employee) {
            LeaveRequest::query()->where('employee_id', $employee->id)->forceDelete();
            LeaveBalance::query()->where('employee_id', $employee->id)->forceDelete();
        });

        return redirect()->route('admin.leave-management.index')
            ->with('success', "Leave data for {$employee->full_name} has been reset.");
    }
}
