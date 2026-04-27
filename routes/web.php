<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\PortalController as AdminPortalController;
use App\Http\Controllers\Employee\PortalController as EmployeePortalController;
use App\Http\Controllers\Hr\AnnouncementController;
use App\Http\Controllers\Hr\DashboardController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\OperationsController;
use App\Models\AnnouncementNotification;
use App\Models\User;
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

    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->whereNumber('employee')->name('employees.destroy');

    Route::post('/employees/{employee}/resend-credentials', [EmployeeController::class, 'resendCredentials'])->whereNumber('employee')->name('employees.resend-credentials');

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

    Route::get('/leave-management', [OperationsController::class, 'leaveManagement'])->name('leave.index');

    Route::post('/leave-management/upload', [OperationsController::class, 'uploadLeaves'])->name('leaves.upload');

    Route::post('/leave-management/clear', [OperationsController::class, 'clearLeaves'])->name('leaves.clear');

    Route::post('/leave-management/clear/{employee}', [OperationsController::class, 'clearEmployeeLeaves'])->whereNumber('employee')->name('leaves.clear-employee');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');

    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('/notifications', function () {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->announcementNotifications()
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        $notifications = AnnouncementNotification::query()
            ->with('announcement')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('hr.notifications', [
            'notifications' => $notifications,
        ]);
    })->name('notifications.index');
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

    Route::get('/attendance-dtr', [EmployeePortalController::class, 'attendance'])->name('attendance');

    Route::get('/leave-monitoring', [EmployeePortalController::class, 'leave'])->name('leave');

    Route::get('/notifications', function () {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->announcementNotifications()
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        $notifications = AnnouncementNotification::query()
            ->with('announcement')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('employee.notifications', [
            'notifications' => $notifications,
        ]);
    })->name('notifications');

    Route::get('/account', [EmployeePortalController::class, 'account'])->name('account');

    Route::post('/account', [EmployeePortalController::class, 'updateAccount'])->name('account.update');

    Route::post('/account/change-password', [EmployeePortalController::class, 'changePassword'])->name('account.change-password');
});

// Admin (User) Module Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'user.type:1'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', [AdminPortalController::class, 'dashboard'])->name('dashboard');

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