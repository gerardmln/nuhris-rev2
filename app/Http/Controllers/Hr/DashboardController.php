<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalEmployees = Employee::count();
        $pendingCredentials = Employee::where('resume_last_updated_at', '<', now()->subMonths(6))->count();
        $presentToday = Employee::where('status', 'active')->count();
        $expiringLicenses = 0;

        // Get latest announcements
        $announcements = Announcement::where('is_published', true)
            ->whereDate('published_at', '<=', now())
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
