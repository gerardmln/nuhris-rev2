<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\PortalController as AdminPortalController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OperationsController as AdminOperationsController;
use App\Http\Controllers\Admin\ScheduleManagementController as AdminScheduleManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\ActivityLogsController;
use App\Http\Controllers\Employee\PortalController as EmployeePortalController;
use App\Http\Controllers\Employee\WfhMonitoringController as EmployeeWfhMonitoringController;
use App\Http\Controllers\Hr\AnnouncementController;
use App\Http\Controllers\Hr\DashboardController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\OperationsController;
use App\Http\Controllers\Hr\ScheduleManagementController;
use App\Http\Controllers\Hr\WfhMonitoringController as HrWfhMonitoringController;
use App\Models\AnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// HR Module Routes
Route::prefix('hr')->middleware(['auth', 'user.type:2'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.show');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');

    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->whereNumber('employee')->name('employees.show');

    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->whereNumber('employee')->name('employees.edit');

    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->whereNumber('employee')->name('employees.update');

    // Sensitive actions removed for HR: delete and resend credentials are admin-only now.

    Route::get('/employees/profile', [OperationsController::class, 'profile'])->name('employees.profile');

    Route::get('/credentials', [OperationsController::class, 'credentials'])->name('credentials.index');

    Route::get('/credentials/{credential}/view', [OperationsController::class, 'viewCredentialFile'])->whereNumber('credential')->name('credentials.view');

    Route::post('/credentials/{credential}/approve', [OperationsController::class, 'approveCredential'])->whereNumber('credential')->name('credentials.approve');

    Route::post('/credentials/{credential}/reject', [OperationsController::class, 'rejectCredential'])->whereNumber('credential')->name('credentials.reject');

    Route::get('/timekeeping', [OperationsController::class, 'timekeeping'])->name('timekeeping.index');

    Route::post('/biometrics/upload', [OperationsController::class, 'uploadBiometrics'])->name('biometrics.upload');

    Route::post('/biometrics/clear', [OperationsController::class, 'clearBiometrics'])->name('biometrics.clear');

    Route::post('/biometrics/clear/{employee}', [OperationsController::class, 'clearEmployeeAttendance'])->whereNumber('employee')->name('biometrics.clear-employee');

    Route::get('/timekeeping/daily-time-record', [OperationsController::class, 'dailyTimeRecord'])->name('timekeeping.dtr');

    Route::get('/timekeeping/daily-time-record/export-pdf', [OperationsController::class, 'exportDtrPdf'])->name('timekeeping.dtr.export-pdf');

    Route::get('/timekeeping/daily-time-record/export-excel', [OperationsController::class, 'exportDtrExcel'])->name('timekeeping.dtr.export-excel');

    Route::get('/wfh-monitoring', [HrWfhMonitoringController::class, 'index'])->name('wfh-monitoring.index');
    Route::get('/wfh-monitoring/{submission}/view', [HrWfhMonitoringController::class, 'viewFile'])->whereNumber('submission')->name('wfh-monitoring.view');
    Route::post('/wfh-monitoring/{submission}/approve', [HrWfhMonitoringController::class, 'approve'])->whereNumber('submission')->name('wfh-monitoring.approve');
    Route::post('/wfh-monitoring/{submission}/decline', [HrWfhMonitoringController::class, 'decline'])->whereNumber('submission')->name('wfh-monitoring.decline');

    Route::get('/schedule-management', [ScheduleManagementController::class, 'index'])->name('schedules.index');
    Route::post('/schedule-management/{submission}/approve', [ScheduleManagementController::class, 'approve'])->whereNumber('submission')->name('schedules.approve');
    Route::post('/schedule-management/{submission}/decline', [ScheduleManagementController::class, 'decline'])->whereNumber('submission')->name('schedules.decline');
    Route::post('/schedule-management/{submission}/clear', [ScheduleManagementController::class, 'clear'])->whereNumber('submission')->name('schedules.clear');

    Route::get('/leave-management', [OperationsController::class, 'leaveManagement'])->name('leave.index');

    Route::post('/leave-management/upload', [OperationsController::class, 'uploadLeaves'])->name('leaves.upload');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    Route::delete('/announcements/clear-all', function (Request $request) {
        $cleared = \App\Models\Announcement::query()
            ->where(function ($query) {
                $query->whereNull('target_employee_type')
                    ->orWhereIn('target_employee_type', ['faculty', 'admin_support']);
            })
            ->forceDelete();

        return redirect()->route('announcements.index')->with('success', $cleared > 0 ? 'All announcements were cleared.' : 'No announcements to clear.');
    })->name('announcements.clear-all');

    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');

    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('/notifications', function () {
        $notifications = AnnouncementNotification::query()
            ->visible()
            ->with('announcement')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('hr.notifications', [
            'notifications' => $notifications,
        ]);
    })->name('notifications.index');

    Route::delete('/notifications/clear-all', function (Request $request) {
        $deleted = AnnouncementNotification::query()
            ->where('user_id', $request->user()?->id)
            ->delete();

        return redirect()->route('notifications.index')->with('success', $deleted > 0 ? 'All notifications were cleared.' : 'No notifications to clear.');
    })->name('notifications.clear-all');

    Route::post('/notifications/read-all', function (Request $request) {
        $updated = AnnouncementNotification::query()
            ->visible()
            ->where('user_id', $request->user()?->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->route('notifications.index')->with('success', $updated > 0 ? 'All notifications were marked as read.' : 'No unread notifications to mark as read.');
    })->name('notifications.read-all');

    Route::get('/notifications/{notification}/open', function (Request $request, AnnouncementNotification $notification) {
        abort_unless($notification->user_id === $request->user()?->id, 403);

        if (! $notification->is_read) {
            $notification->forceFill([
                'is_read' => true,
                'read_at' => now(),
            ])->save();
        }

        return redirect()->to($notification->redirect_url ?: route('notifications.index'));
    })->name('notifications.open');
});

// Employee (User) Module Routes
Route::prefix('employee')->name('employee.')->middleware(['auth', 'user.type:3'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('employee.dashboard');
    });

    Route::get('/dashboard', [EmployeePortalController::class, 'dashboard'])->name('dashboard');

    Route::get('/credentials', [EmployeePortalController::class, 'credentials'])->name('credentials');

    Route::get('/credentials/upload', [EmployeePortalController::class, 'credentialsUpload'])->name('credentials.upload');

    Route::post('/credentials/upload', [EmployeePortalController::class, 'storeCredential'])->name('credentials.upload.store');

    Route::get('/credentials/{credential}/view', [EmployeePortalController::class, 'viewCredentialFile'])
        ->whereNumber('credential')
        ->name('credentials.view');

    Route::delete('/credentials/{credential}', [EmployeePortalController::class, 'destroyCredential'])
        ->whereNumber('credential')
        ->name('credentials.destroy');

    Route::get('/attendance-dtr', [EmployeePortalController::class, 'attendance'])->name('attendance');
    Route::post('/attendance-dtr/schedule', [EmployeePortalController::class, 'storeSchedule'])->name('attendance.schedule.store');

    Route::get('/wfh-monitoring', [EmployeeWfhMonitoringController::class, 'index'])->name('wfh-monitoring.index');
    Route::get('/wfh-monitoring/upload', [EmployeeWfhMonitoringController::class, 'create'])->name('wfh-monitoring.upload');
    Route::post('/wfh-monitoring/upload', [EmployeeWfhMonitoringController::class, 'store'])->name('wfh-monitoring.store');
    Route::get('/wfh-monitoring/{submission}/view', [EmployeeWfhMonitoringController::class, 'viewFile'])->whereNumber('submission')->name('wfh-monitoring.view');

    Route::get('/leave-monitoring', [EmployeePortalController::class, 'leave'])->name('leave');

    Route::get('/notifications', function () {
        $notifications = AnnouncementNotification::query()
            ->visible()
            ->with('announcement')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('employee.notifications', [
            'notifications' => $notifications,
        ]);
    })->name('notifications');

    Route::delete('/notifications/clear-all', function (Request $request) {
        $deleted = AnnouncementNotification::query()
            ->where('user_id', $request->user()?->id)
            ->delete();

        return redirect()->route('employee.notifications')->with('success', $deleted > 0 ? 'All notifications were cleared.' : 'No notifications to clear.');
    })->name('notifications.clear-all');

    Route::post('/notifications/read-all', function (Request $request) {
        $updated = AnnouncementNotification::query()
            ->visible()
            ->where('user_id', $request->user()?->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->route('employee.notifications')->with('success', $updated > 0 ? 'All notifications were marked as read.' : 'No unread notifications to mark as read.');
    })->name('notifications.read-all');

    Route::get('/notifications/{notification}/open', function (Request $request, AnnouncementNotification $notification) {
        abort_unless($notification->user_id === $request->user()?->id, 403);

        if (! $notification->is_read) {
            $notification->forceFill([
                'is_read' => true,
                'read_at' => now(),
            ])->save();
        }

        return redirect()->to($notification->redirect_url ?: route('employee.notifications'));
    })->name('notifications.open');

    Route::get('/account', [EmployeePortalController::class, 'account'])->name('account');

    Route::post('/account', [EmployeePortalController::class, 'updateAccount'])->name('account.update');

    Route::post('/account/change-password', [EmployeePortalController::class, 'changePassword'])->name('account.change-password');
});

// Admin (User) Module Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'user.type:1'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // ========== CREDENTIAL MANAGEMENT (ADMIN-ONLY) ==========
    Route::prefix('credentials')->name('credentials.')->group(function () {
        Route::get('/', [AdminOperationsController::class, 'credentials'])->name('index');
        Route::get('/{credential}/edit', [AdminOperationsController::class, 'editCredential'])->whereNumber('credential')->name('edit');
        Route::put('/{credential}', [AdminOperationsController::class, 'updateCredential'])->whereNumber('credential')->name('update');
        Route::delete('/{credential}', [AdminOperationsController::class, 'deleteCredential'])->whereNumber('credential')->name('destroy');
        Route::delete('/', [AdminOperationsController::class, 'clearAllCredentials'])->name('clear-all');
    });

    // ========== DTR / TIMEKEEPING EDITING (ADMIN-ONLY) ==========
    Route::prefix('dtr')->name('dtr.')->group(function () {
        Route::get('/', [AdminOperationsController::class, 'dtrIndex'])->name('index');
        Route::get('/{record}/edit', [AdminOperationsController::class, 'editDtrRecord'])->whereNumber('record')->name('edit');
        Route::put('/{record}', [AdminOperationsController::class, 'updateDtrRecord'])->whereNumber('record')->name('update');
    });

    // ========== WFH MANAGEMENT (ADMIN-ONLY) ==========
    Route::prefix('wfh-monitoring')->name('wfh-monitoring.')->group(function () {
        Route::get('/', [AdminOperationsController::class, 'wfhIndex'])->name('index');
        Route::delete('/', [AdminOperationsController::class, 'clearAllWfh'])->name('clear-all');
    });

    // ========== LEAVE MANAGEMENT (ADMIN-ONLY) ==========
    Route::prefix('leave-management')->name('leave.')->group(function () {
        Route::get('/', [AdminOperationsController::class, 'leaveIndex'])->name('index');
        Route::delete('/', [AdminOperationsController::class, 'clearAllLeaves'])->name('clear-all');
        Route::delete('/employee/{employee}', [AdminOperationsController::class, 'resetEmployeeLeaves'])->whereNumber('employee')->name('reset-employee');
    });

    // ========== SCHEDULE MANAGEMENT (ADMIN-ONLY) ==========
    Route::prefix('schedule-management')->name('schedules.')->group(function () {
        Route::get('/', [AdminScheduleManagementController::class, 'index'])->name('index');
        Route::post('/{submission}/approve', [AdminScheduleManagementController::class, 'approve'])->whereNumber('submission')->name('approve');
        Route::post('/{submission}/decline', [AdminScheduleManagementController::class, 'decline'])->whereNumber('submission')->name('decline');
        Route::delete('/employee/{employee}', [AdminScheduleManagementController::class, 'resetEmployee'])->whereNumber('employee')->name('employee.reset');
        Route::delete('/', [AdminScheduleManagementController::class, 'resetAll'])->name('reset-all');
    });

    // ========== ROLE MANAGEMENT ==========
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])->name('index');
        Route::put('/{user}', [RoleManagementController::class, 'updateRole'])->whereNumber('user')->name('update');
    });

    // ========== ACTIVITY LOGS (PLACEHOLDER) ==========
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [ActivityLogsController::class, 'index'])->name('index');
    });

    // ========== ADMIN EMPLOYEES ==========
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\EmployeeController::class, 'index'])->name('index');
        Route::get('/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'show'])->whereNumber('employee')->name('show');
        Route::get('/{employee}/edit', [\App\Http\Controllers\Admin\EmployeeController::class, 'edit'])->whereNumber('employee')->name('edit');
        Route::put('/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'update'])->whereNumber('employee')->name('update');
        Route::delete('/{employee}', [\App\Http\Controllers\Admin\EmployeeController::class, 'destroy'])->whereNumber('employee')->name('destroy');
        Route::post('/{employee}/resend-credentials', [\App\Http\Controllers\Admin\EmployeeController::class, 'resendCredentials'])->whereNumber('employee')->name('resend-credentials');
    });

    // ========== LEGACY ADMIN ROUTES (KEPT FOR BACKWARD COMPATIBILITY) ==========
    Route::get('/user-management/accounts', [AdminPortalController::class, 'userAccounts'])->name('users.accounts');

    Route::delete('/user-management/accounts/{user}', [AdminPortalController::class, 'destroyUser'])->whereNumber('user')->name('users.accounts.destroy');

    Route::get('/user-management/role-assignment', [AdminPortalController::class, 'roleAssignment'])->name('users.role-assignment');

    Route::post('/user-management/role-assignment', [AdminPortalController::class, 'updateUserRole'])->name('users.role-assignment.update');

    Route::get('/user-management/rbac-permissions', [AdminPortalController::class, 'rbac'])->name('users.rbac');

    Route::post('/user-management/rbac-permissions', [AdminPortalController::class, 'saveRbac'])->name('users.rbac.save');

    Route::get('/policy/cutoff-schedules', [AdminPortalController::class, 'cutoffSchedules'])->name('policy.cutoff');

    Route::post('/policy/cutoff-schedules/periods', [AdminPortalController::class, 'storeCutoffPeriod'])->name('policy.cutoff.periods.store');

    Route::post('/policy/cutoff-schedules/settings', [AdminPortalController::class, 'updateCutoffSettings'])->name('policy.cutoff.settings.update');

    Route::get('/policy/leave-rules', [AdminPortalController::class, 'leaveRules'])->name('policy.leave');

    Route::post('/policy/leave-rules', [AdminPortalController::class, 'storeLeaveType'])->name('policy.leave.store');

    Route::get('/policy/compliance-rules', [AdminPortalController::class, 'complianceRules'])->name('policy.compliance');

    Route::get('/policy/notification-templates', [AdminPortalController::class, 'notificationTemplates'])->name('policy.templates');

    Route::post('/policy/notification-templates', [AdminPortalController::class, 'storeNotificationTemplate'])->name('policy.templates.store');

    Route::get('/integration/api-integrations', [AdminPortalController::class, 'apiIntegrations'])->name('integration.api');

    Route::post('/integration/api-integrations', [AdminPortalController::class, 'storeIntegration'])->name('integration.api.store');

    Route::post('/integration/api-integrations/keys', [AdminPortalController::class, 'updateApiKey'])->name('integration.api.keys.update');

    Route::get('/integration/audit-logs', [AdminPortalController::class, 'auditLogs'])->name('integration.audit');

    Route::get('/integration/data-validation', [AdminPortalController::class, 'dataValidation'])->name('integration.validation');

    Route::post('/integration/data-validation', [AdminPortalController::class, 'storeValidationRule'])->name('integration.validation.store');

    Route::post('/policy/compliance-rules', [AdminPortalController::class, 'storeValidationRule'])->name('policy.compliance.store');

    Route::get('/integration/backup-security', [AdminPortalController::class, 'backupSecurity'])->name('integration.backup');

    Route::get('/report-oversight', [AdminPortalController::class, 'reportOversight'])->name('reports');
});

// Default route
Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return match ((int) Auth::user()->user_type) {
        1 => redirect()->route('admin.dashboard'),
        2 => redirect()->route('dashboard'),
        default => redirect()->route('employee.dashboard'),
    };
})->name('home');

/*
Keep profile protected, or remove this whole group if you want everything public.
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';