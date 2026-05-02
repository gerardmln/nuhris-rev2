<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Core stats
        $totalEmployees = Employee::query()->count();
        $activeFaculty = Employee::query()->where('employment_type', 'faculty')->count();
        
        // Credentials stats
        $expiringPrc = EmployeeCredential::query()
            ->where('credential_type', 'prc')
            ->where('status', 'verified')
            ->get()
            ->filter(fn (EmployeeCredential $cred) => $cred->isExpiringSoon())
            ->count();
        
        $pendingVerifications = EmployeeCredential::query()
            ->where('status', 'pending')
            ->count();

        // Attendance stats (today)
        $todayRecords = AttendanceRecord::query()
            ->whereDate('record_date', Carbon::today())
            ->get();
        
        $todayPresent = $todayRecords->filter(fn ($r) => $r->status === 'present')->count();
        $totalToday = $todayRecords->count();
        $attendanceRate = $totalToday > 0 ? round(($todayPresent / $totalToday) * 100) : 0;

        // Compliance rate (credentials verified out of total employees)
        $verifiedCredentials = EmployeeCredential::query()
            ->where('status', 'verified')
            ->distinct('employee_id')
            ->count('employee_id');
        $complianceRate = $totalEmployees > 0 ? round(($verifiedCredentials / $totalEmployees) * 100) : 0;

        // Recent activities
        $recentActivities = [
            'Admin module initialized successfully',
            $totalEmployees . ' employees in system',
            $pendingVerifications . ' credentials pending verification',
            $expiringPrc . ' PRC credentials expiring soon',
            'Dashboard loaded at ' . Carbon::now()->format('Y-m-d H:i:s'),
        ];

        return view('admin.dashboard', [
            'stats' => [
                'total_employees' => $totalEmployees,
                'active_faculty' => $activeFaculty,
                'compliance_rate' => $complianceRate,
                'attendance_rate' => $attendanceRate,
                'expiring_prc' => $expiringPrc,
                'pending_verifications' => $pendingVerifications,
            ],
            'recentActivities' => $recentActivities,
        ]);
    }
}
