<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendarEntry;
use App\Models\Announcement;
use App\Models\AnnouncementNotification;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\EmployeeScheduleSubmission;
use App\Models\WfhMonitoringSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
class DashboardController extends Controller
{
    public function index(): View
    {
        $totalEmployees = Employee::count();
        $pendingCredentials = Employee::where('resume_last_updated_at', '<', now()->subMonths(6))->count();
        $presentToday = Employee::where('status', 'active')->count();
        $expiringLicenses = 0;


        $expiringCredentialsCount = \App\Models\EmployeeCredential::query()
            ->where('status', 'verified')
            ->get()
            ->filter(fn (\App\Models\EmployeeCredential $credential) => $credential->isExpiringSoon())
            ->count();

        $pendingScheduleApprovalsCount = EmployeeScheduleSubmission::query()
            ->where('status', EmployeeScheduleSubmission::STATUS_PENDING)
            ->count();

        $pendingWfhApprovalsCount = WfhMonitoringSubmission::query()
            ->where('status', WfhMonitoringSubmission::STATUS_PENDING)
            ->count();

        $unreadNotificationsCount = Auth::check()
            ? AnnouncementNotification::query()
                ->visible()
                ->where('user_id', Auth::id())
                ->where('is_read', false)
                ->count()
            : 0;

        $actionRequiredCards = [
            [
                'title' => 'Expiring Credentials',
                'count' => $expiringCredentialsCount,
                'description' => 'Verified files that are nearing expiration.',
                'href' => route('credentials.index', ['status' => 'expiring']),
                'tone' => 'amber',
                'empty_label' => 'No expiring credentials',
            ],
            [
                'title' => 'Schedule Approvals',
                'count' => $pendingScheduleApprovalsCount,
                'description' => 'Employee schedule submissions waiting for review.',
                'href' => route('schedules.index', ['status' => 'pending']),
                'tone' => 'blue',
                'empty_label' => 'No schedules pending review',
            ],
            [
                'title' => 'WFH Reviews',
                'count' => $pendingWfhApprovalsCount,
                'description' => 'WFH monitoring submissions waiting for approval.',
                'href' => route('wfh-monitoring.index', ['status' => 'pending']),
                'tone' => 'emerald',
                'empty_label' => 'No WFH submissions pending',
            ],
            [
                'title' => 'Unread Notifications',
                'count' => $unreadNotificationsCount,
                'description' => 'Announcements and HR notices you have not opened yet.',
                'href' => route('notifications.index'),
                'tone' => 'slate',
                'empty_label' => 'No unread notifications',
            ],
        ];
        $academicCalendarEntries = AcademicCalendarEntry::query()
            ->orderBy('event_date')
            ->get()
            ->map(fn (AcademicCalendarEntry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'entry_type' => $entry->entry_type,
                'day_type' => $entry->day_type,
                'event_date' => $entry->event_date->toDateString(),
                'description' => $entry->description,
                'type_label' => $entry->type_label,
                'badge_class' => $entry->badge_class,
            ])
            ->values();

        // Get latest announcements
        $announcements = Announcement::where('is_published', true)
            ->whereDate('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('target_employee_type')
                    ->orWhereIn('target_employee_type', ['faculty', 'admin_support']);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', now());
            })
            ->latest('published_at')
            ->limit(3)
            ->get();

        // Dashboard stats
        $stats = [
            'total_employees' => $totalEmployees,
            'pending_credentials' => $pendingCredentials,
            'present_today' => $presentToday,
            'expiring_licenses' => $expiringLicenses,
        ];

        return view('hr.dashboard', [
            'stats' => $stats,
            'announcements' => $announcements,
            'academicCalendarEntries' => $academicCalendarEntries,
            'actionRequiredCards' => $actionRequiredCards,
            'departments' => Department::query()->schools()->orderBy('name')->get(),
            'employmentTypes' => config('hris.employment_types', []),
            'employeePositions' => array_values(array_unique(array_merge(
                config('hris.faculty_positions', []),
                config('hris.admin_support_offices', [])
            ))),
            'facultyRankings' => config('hris.faculty_rankings', []),
            'officeAudiences' => config('hris.admin_support_offices', []),
        ]);
    }
}
