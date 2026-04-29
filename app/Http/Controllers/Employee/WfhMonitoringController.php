<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\Employee;
use App\Models\User;
use App\Models\WfhMonitoringSubmission;
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
        $employee = Employee::query()->where('email', $request->user()->email)->first();

        $submissions = $employee
            ? WfhMonitoringSubmission::query()
                ->with(['reviewer'])
                ->where('employee_id', $employee->id)
                ->orderByDesc('wfh_date')
                ->orderByDesc('submitted_at')
                ->get()
            : collect();

        $mapped = $submissions->map(function (WfhMonitoringSubmission $submission): array {
            return [
                'id' => $submission->id,
                'date' => $submission->wfh_date?->format('M d, Y') ?? '—',
                'date_raw' => $submission->wfh_date?->format('Y-m-d'),
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

        return view('employee.wfh-monitoring', [
            'submissions' => $mapped,
            'stats' => [
                'all' => $mapped->count(),
                'pending' => $mapped->where('status', WfhMonitoringSubmission::STATUS_PENDING)->count(),
                'approved' => $mapped->where('status', WfhMonitoringSubmission::STATUS_APPROVED)->count(),
                'declined' => $mapped->where('status', WfhMonitoringSubmission::STATUS_DECLINED)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $employee = Employee::query()->with('department')->where('email', $request->user()->email)->first();

        return view('employee.wfh-monitoring-upload', [
            'employee' => $employee,
        ]);
    }

    public function store(Request $request, SupabaseStorageService $storage): RedirectResponse
    {
        $validated = $request->validate([
            'wfh_date' => ['required', 'date'],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i', 'after:time_in'],
            'monitoring_file' => ['required', 'file', 'max:20480'],
        ], [
            'wfh_date.required' => 'Please choose the WFH date first.',
            'wfh_date.date' => 'The WFH date is invalid.',
            'time_in.required' => 'Please enter the expected time in.',
            'time_in.date_format' => 'The time in must be a valid time.',
            'time_out.required' => 'Please enter the expected time out.',
            'time_out.date_format' => 'The time out must be a valid time.',
            'time_out.after' => 'The time out must be later than the time in.',
            'monitoring_file.required' => 'Please choose a file first.',
            'monitoring_file.max' => 'The file is too large. Maximum allowed is 20 MB.',
        ]);

        $employee = Employee::query()->where('email', $request->user()->email)->first();

        if (! $employee) {
            return redirect()->route('employee.wfh-monitoring.index')->with('error', 'Employee profile not found. Please contact HR.');
        }

        $existingSubmission = WfhMonitoringSubmission::query()
            ->where('employee_id', $employee->id)
            ->whereDate('wfh_date', $validated['wfh_date'])
            ->whereIn('status', [WfhMonitoringSubmission::STATUS_PENDING, WfhMonitoringSubmission::STATUS_APPROVED])
            ->exists();

        if ($existingSubmission) {
            return back()->withInput()->with('error', 'You already have a pending or approved WFH submission for that date.');
        }

        $filePath = null;
        $originalFilename = null;

        try {
            $file = $request->file('monitoring_file');
            $filePath = $storage->uploadFile($file, 'employee-'.$employee->id.'/wfh-monitoring');
            $originalFilename = $file->getClientOriginalName();
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', 'File upload failed: '.$exception->getMessage());
        }

        DB::transaction(function () use ($employee, $request, $validated, $filePath, $originalFilename): void {
            WfhMonitoringSubmission::create([
                'employee_id' => $employee->id,
                'submitted_by' => $request->user()->id,
                'wfh_date' => $validated['wfh_date'],
                'time_in' => $validated['time_in'],
                'time_out' => $validated['time_out'],
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'status' => WfhMonitoringSubmission::STATUS_PENDING,
                'submitted_at' => now(),
            ]);

            $announcement = Announcement::forceCreate([
                'title' => 'New WFH monitoring sheet uploaded',
                'content' => sprintf('%s uploaded a WFH monitoring sheet for %s and is waiting for HR review.', $employee->full_name, Carbon::parse($validated['wfh_date'])->format('F d, Y')),
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
                'announcement_id' => $announcement->id,
                'user_id' => $userId,
                'is_read' => false,
                'read_at' => null,
                'redirect_url' => route('wfh-monitoring.index'),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if ($rows) {
                AnnouncementNotification::insert($rows);
            }
        });

        return redirect()->route('employee.wfh-monitoring.index')->with('success', 'Your WFH monitoring sheet was uploaded and is now pending HR review.');
    }

    public function viewFile(Request $request, WfhMonitoringSubmission $submission, SupabaseStorageService $storage): RedirectResponse
    {
        $employee = Employee::query()->where('email', $request->user()->email)->firstOrFail();

        abort_unless($submission->employee_id === $employee->id, 403);

        if (! $submission->file_path) {
            return back()->with('error', 'No file was attached to this WFH submission.');
        }

        $url = $storage->createSignedUrl($submission->file_path, 300);

        if (! $url) {
            return back()->with('error', 'Unable to generate a download link. Please try again.');
        }

        return redirect()->away($url);
    }
}
