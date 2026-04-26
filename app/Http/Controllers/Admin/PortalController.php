<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\AdminConfig;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        $stats = $this->baseStats();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentActivities' => $this->recentActivities(),
        ]);
    }

    public function userAccounts(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $employee = Employee::query()->where('email', $user->email)->with('department')->first();

                return [
                    'id' => $user->id,
                    'user_type' => (int) $user->user_type,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->roleLabel($user->user_type),
                    'department' => $employee?->department?->name ?? 'Unassigned',
                    'status' => $user->email_verified_at ? 'Active' : 'Inactive',
                    'last_login' => optional($user->updated_at)->format('M d, Y h:i A') ?? 'N/A',
                ];
            });

        return view('admin.user-accounts', [
            'users' => $users,
            'roles' => ['Admin', 'HR Personnel', 'Employee'],
            'roleOptionMap' => [
                'Admin' => User::TYPE_ADMIN,
                'HR Personnel' => User::TYPE_HR,
                'Employee' => User::TYPE_EMPLOYEE,
            ],
        ]);
    }

    public function roleAssignment(): View
    {
        $users = User::query()->orderBy('name')->get();

        $roles = collect([
            ['name' => 'Administrator', 'description' => 'Full system access'],
            ['name' => 'HR Personnel', 'description' => 'HR records and compliance'],
            ['name' => 'Employee', 'description' => 'Own records and leave'],
        ]);

        $assignableUsers = $users->map(function (User $user) {
            return [
                'initials' => str($user->name)->explode(' ')->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join(''),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $this->roleLabel($user->user_type),
                'user_id' => $user->id,
                'user_type' => (int) $user->user_type,
            ];
        });

        return view('admin.role-assignment', [
            'roles' => $roles,
            'assignableUsers' => $assignableUsers,
            'roleOptions' => $roles->pluck('name')->all(),
            'roleOptionMap' => [
                'Administrator' => User::TYPE_ADMIN,
                'HR Personnel' => User::TYPE_HR,
                'Employee' => User::TYPE_EMPLOYEE,
            ],
        ]);
    }

    public function rbac(): View
    {
        $roles = ['Admin', 'HR Personnel', 'Employee'];
        $modules = ['User Management', 'Role Management', 'Employee Records', 'Leave Management', 'Compliance Tracking', 'DTR / Timekeeping', 'Reports', 'System Settings', 'Audit Logs'];
        $permissions = ['View', 'Create', 'Edit', 'Approve', 'Delete'];

        $matrix = $this->config('admin.rbac.matrix', [
            'Admin' => ['View', 'Create', 'Edit', 'Approve', 'Delete'],
            'HR Personnel' => ['View', 'Create', 'Edit', 'Approve'],
            'Employee' => ['View'],
        ]);

        return view('admin.rbac', compact('roles', 'modules', 'permissions', 'matrix'));
    }

    public function cutoffSchedules(): View
    {
        $periods = collect($this->config('admin.cutoff.periods', [
            [
                'period' => now()->format('F Y').' - 1st Half',
                'start_date' => now()->startOfMonth()->format('M d, Y'),
                'end_date' => now()->startOfMonth()->addDays(14)->format('M d, Y'),
                'pay_date' => now()->startOfMonth()->addDays(19)->format('M d, Y'),
                'status' => 'Active',
            ],
            [
                'period' => now()->format('F Y').' - 2nd Half',
                'start_date' => now()->startOfMonth()->addDays(15)->format('M d, Y'),
                'end_date' => now()->endOfMonth()->format('M d, Y'),
                'pay_date' => now()->endOfMonth()->addDays(5)->format('M d, Y'),
                'status' => 'Upcoming',
            ],
        ]));

        $schedules = collect($this->config('admin.cutoff.work_schedules', Department::query()->orderBy('name')->pluck('name')->map(fn ($name) => [
            'name' => $name,
            'time' => '07:00 AM - 05:00 PM',
        ])->values()->all()));

        $settings = $this->config('admin.cutoff.settings', [
            'frequency' => 'Semi-monthly',
            'pay_delay' => 5,
            'generate_ahead' => 3,
        ]);

        return view('admin.cutoff-schedules', [
            'periods' => $periods,
            'schedules' => $schedules,
            'settings' => $settings,
        ]);
    }

    public function leaveRules(): View
    {
        $leaveTypes = collect($this->config('admin.leave.types', [
            ['type' => 'Vacation Leave', 'accrual' => '1.25 days/month', 'max' => 15, 'rollover' => 'Active', 'applies_to' => 'All'],
            ['type' => 'Sick Leave', 'accrual' => '1.25 days/month', 'max' => 10, 'rollover' => 'No', 'applies_to' => 'All'],
            ['type' => 'Emergency Leave', 'accrual' => 'Fixed', 'max' => 3, 'rollover' => 'No', 'applies_to' => 'All'],
        ]));

        $allocations = collect($this->config('admin.leave.allocations', [
            ['employee_type' => 'Regular Employee', 'vacation' => 15, 'sick' => 15, 'emergency' => 3],
            ['employee_type' => 'Probationary', 'vacation' => 5, 'sick' => 5, 'emergency' => 3],
            ['employee_type' => 'Faculty (Full-time)', 'vacation' => 15, 'sick' => 15, 'emergency' => 3],
        ]))->map(fn (array $row) => [...$row, 'total' => $row['vacation'] + $row['sick'] + $row['emergency']]);

        return view('admin.leave-rules', [
            'leaveTypes' => $leaveTypes,
            'allocations' => $allocations,
            'stats' => [
                'leave_types' => $leaveTypes->count(),
                'with_rollover' => $leaveTypes->where('rollover', 'Active')->count(),
                'employee_types' => $allocations->count(),
                'max_vl_credits' => $leaveTypes->where('type', 'Vacation Leave')->first()['max'] ?? 0,
            ],
        ]);
    }

    public function complianceRules(): View
    {
        // Same round-trip optimization as baseStats(): 3 counts → 1 query.
        $oneYearAgo = now()->subYear()->toDateTimeString();
        $fiveMonthsAgo = now()->subMonths(5)->toDateTimeString();

        $row = DB::selectOne(
            'SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE resume_last_updated_at >= ?) AS recently_updated,
                COUNT(*) FILTER (WHERE resume_last_updated_at < ?) AS expiring_soon
             FROM employees
             WHERE deleted_at IS NULL',
            [$oneYearAgo, $fiveMonthsAgo]
        );

        $employees = (int) ($row->total ?? 0);
        $recentlyUpdated = (int) ($row->recently_updated ?? 0);
        $expiringSoon = (int) ($row->expiring_soon ?? 0);
        $validationRules = collect($this->config('admin.validation.rules', ['PRC Expiration', 'Phone Number', 'PRC License Format', 'Employee ID', 'Date of Birth', 'Salary Range']));

        return view('admin.compliance-rules', [
            'stats' => [
                'ched_compliance' => $employees > 0 ? round(($recentlyUpdated / $employees) * 100) : 0,
                'prc_valid' => sprintf('%d/%d', $recentlyUpdated, $employees),
                'expiring_soon' => $expiringSoon,
                'pending_documents' => max($employees - $recentlyUpdated, 0),
            ],
            'chedItems' => [
                'Faculty Qualifications Report',
                'Faculty Loading Report',
                'Research Output Documentation',
                'Faculty Development Program Report',
                'Student-Faculty Ratio Report',
                'Curriculum Vitae Updates',
            ],
            'prcRules' => [
                'License Number Verification',
                'Expiration Date Check',
                'Renewal Reminder',
                'Auto-suspend Access',
                'Document Upload Required',
            ],
            'alertRules' => $validationRules,
        ]);
    }

    public function notificationTemplates(): View
    {
        $templates = $this->config('admin.notifications.templates', [
            'email' => ['Welcome Email', 'Password Reset', 'Leave Approved', 'Leave Rejected', 'PRC Expiration Warning', 'Compliance Reminder'],
            'sms' => ['OTP Verifications', 'Leave Status', 'PRC Alert'],
            'inapp' => ['System Maintenance', 'New Feature', 'Policy Update', 'Compliance Alert'],
        ]);

        return view('admin.notification-templates', [
            'templates' => $templates,
            'tokens' => ['{{user_name}}', '{{user_email}}', '{{employee_id}}', '{{department}}', '{{leave_type}}', '{{leave_dates}}', '{{leave_status}}', '{{approver_name}}', '{{prc_number}}', '{{compliance_requirement}}', '{{deadline_date}}', '{{current_date}}'],
            'stats' => [
                'email' => count($templates['email'] ?? []),
                'sms' => count($templates['sms'] ?? []),
                'inapp' => count($templates['inapp'] ?? []),
            ],
        ]);
    }

    public function apiIntegrations(): View
    {
        $integrations = collect($this->config('admin.integrations.items', [
            ['name' => 'PRC License Verification API', 'status' => 'Connected'],
            ['name' => 'CHED Compliance Portal', 'status' => 'Connected'],
            ['name' => 'Email Service', 'status' => 'Connected'],
        ]));

        $apiKeys = $this->config('admin.integrations.keys', [
            'Production API Key' => str_repeat('*', 12),
            'Development API Key' => str_repeat('*', 12),
        ]);

        return view('admin.api-integrations', [
            'integrations' => $integrations,
            'stats' => [
                'total' => $integrations->count(),
                'connected' => $integrations->where('status', 'Connected')->count(),
                'issues' => $integrations->where('status', '!=', 'Connected')->count(),
                'api_calls_today' => number_format(max(Announcement::count() * 53, 0)),
            ],
            'apiKeys' => $apiKeys,
        ]);
    }

    public function auditLogs(): View
    {
        $auditLogs = AdminAuditLog::query()
            ->with('user')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (AdminAuditLog $log) => [
                'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                'user' => $log->user?->name ?? 'System',
                'action' => strtoupper($log->action),
                'module' => $log->module,
                'description' => $log->description,
                'status' => $log->status,
            ]);

        return view('admin.audit-logs', [
            'logs' => $auditLogs,
            'stats' => [
                'total' => AdminAuditLog::query()->whereDate('created_at', today())->count(),
                'success' => AdminAuditLog::query()->whereDate('created_at', today())->where('status', 'Success')->count(),
                'failed' => AdminAuditLog::query()->whereDate('created_at', today())->where('status', 'Failed')->count(),
                'active_users' => User::query()->count(),
            ],
        ]);
    }

    public function dataValidation(): View
    {
        $validationRules = $this->config('admin.validation.rules', ['PRC Expiration', 'Phone Number', 'PRC License Format', 'Employee ID', 'Date of Birth', 'Salary Range']);
        $requiredFields = $this->config('admin.validation.required_fields', ['PRC Expiration', 'Faculty Records', 'Leave Requests', 'Payroll Data']);

        return view('admin.data-validation', [
            'validationRules' => $validationRules,
            'requiredFields' => $requiredFields,
            'stats' => [
                'rules' => count($validationRules),
                'active_rules' => count($validationRules),
                'required_sets' => count($requiredFields),
                'errors_today' => max(User::count() - Employee::count(), 0),
            ],
        ]);
    }

    public function backupSecurity(): View
    {
        $users = User::query()->count();
        $employees = Employee::query()->count();

        return view('admin.backup-security', [
            'stats' => [
                'last_backup' => now()->subHours(4)->diffForHumans(),
                'storage_used' => sprintf('%0.1f GB / 100 GB', 10 + ($employees * 0.7)),
                'failed_logins' => AdminAuditLog::query()->where('module', 'Authentication')->where('status', 'Failed')->whereDate('created_at', today())->count(),
                'security_score' => min(80 + $employees, 100).'/100',
            ],
            'backups' => [
                ['name' => 'Full Backup', 'size' => sprintf('%0.1f GB', 1.2 + ($employees * 0.2))],
                ['name' => 'Incremental', 'size' => '116 MB'],
                ['name' => 'Incremental', 'size' => '243 MB'],
            ],
            'alerts' => [
                'Multiple failed login attempts',
                'Automatic backup completed successfully',
                'Unusual activity detected: 50+ API calls',
                'Security patch applied successfully',
            ],
        ]);
    }

    public function reportOversight(): View
    {
        $stats = $this->baseStats();

        $departmentSummary = Department::query()
            ->withCount('employees')
            ->orderBy('name')
            ->get()
            ->map(function (Department $department) {
                $employees = max($department->employees_count, 1);
                $compliance = min(88 + ($employees * 2), 99);
                $attendance = min(85 + $employees, 98);

                return [
                    'department' => $department->name,
                    'employees' => $department->employees_count,
                    'compliance' => $compliance,
                    'attendance' => $attendance,
                    'status' => $compliance >= 95 ? 'Excellent' : 'Good',
                ];
            });

        return view('admin.report-oversight', [
            'stats' => $stats,
            'departmentSummary' => $departmentSummary,
            'termLabel' => now()->month <= 6 ? '2nd Term '.now()->subYear()->year.'-'.now()->year : '1st Term '.now()->year.'-'.now()->addYear()->year,
        ]);
    }

    public function updateUserRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'user_type' => ['required', 'in:1,2,3'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        $from = $this->roleLabel((int) $user->user_type);
        $toType = (int) $validated['user_type'];
        $to = $this->roleLabel($toType);

        $user->update(['user_type' => $toType]);

        $this->logAction(
            $request,
            'UPDATE',
            'Role Assignment',
            sprintf('Updated %s role from %s to %s.', $user->email, $from, $to),
            ['user_id' => $user->id, 'from' => $from, 'to' => $to]
        );

        return back()->with('success', 'User role updated successfully.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        // Never let an admin delete themselves
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account while logged in.');
        }

        $email = $user->email;
        $role = $this->roleLabel((int) $user->user_type);
        $user->delete();

        $this->logAction(
            $request,
            'DELETE',
            'User Accounts',
            sprintf('Deleted user %s (%s).', $email, $role),
            ['email' => $email, 'role' => $role]
        );

        return back()->with('success', 'User deleted successfully.');
    }

    public function saveRbac(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matrix' => ['nullable', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['string'],
        ]);

        $this->putConfig('admin.rbac.matrix', $validated['matrix'] ?? []);
        $this->logAction($request, 'UPDATE', 'RBAC', 'Updated RBAC permission matrix.');

        return back()->with('success', 'RBAC permissions saved successfully.');
    }

    public function storeCutoffPeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['required', 'date'],
        ]);

        $periods = collect($this->config('admin.cutoff.periods', []));
        $periods->push([
            'period' => $validated['period'],
            'start_date' => Carbon::parse($validated['start_date'])->format('M d, Y'),
            'end_date' => Carbon::parse($validated['end_date'])->format('M d, Y'),
            'pay_date' => Carbon::parse($validated['pay_date'])->format('M d, Y'),
            'status' => Carbon::parse($validated['start_date'])->isFuture() ? 'Upcoming' : 'Active',
        ]);

        $this->putConfig('admin.cutoff.periods', $periods->values()->all());
        $this->logAction($request, 'CREATE', 'Cut-off Schedules', 'Added a payroll cut-off period.', $validated);

        return back()->with('success', 'Cut-off period added successfully.');
    }

    public function updateCutoffSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'frequency' => ['required', 'string', 'max:50'],
            'pay_delay' => ['required', 'integer', 'min:0', 'max:30'],
            'generate_ahead' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        $this->putConfig('admin.cutoff.settings', $validated);
        $this->logAction($request, 'UPDATE', 'Cut-off Schedules', 'Updated cut-off generation settings.', $validated);

        return back()->with('success', 'Cut-off settings saved successfully.');
    }

    public function storeLeaveType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'accrual' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'max' => ['required', 'integer', 'min:0', 'max:365'],
            'applies_to' => ['required', 'string', 'max:100'],
        ]);

        $leaveTypes = collect($this->config('admin.leave.types', []));
        $leaveTypes->push([
            'type' => $validated['type'],
            'accrual' => ($validated['accrual'] ?? 0).' days/month',
            'max' => (int) $validated['max'],
            'rollover' => 'No',
            'applies_to' => $validated['applies_to'],
        ]);

        $this->putConfig('admin.leave.types', $leaveTypes->values()->all());
        $this->logAction($request, 'CREATE', 'Leave Rules', 'Added leave type: '.$validated['type'], $validated);

        return back()->with('success', 'Leave type added successfully.');
    }

    public function storeNotificationTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:email,sms,inapp'],
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $templates = $this->config('admin.notifications.templates', ['email' => [], 'sms' => [], 'inapp' => []]);
        $list = collect($templates[$validated['type']] ?? []);
        if (! $list->contains($validated['name'])) {
            $list->push($validated['name']);
        }

        $templates[$validated['type']] = $list->values()->all();
        $this->putConfig('admin.notifications.templates', $templates);
        $this->logAction($request, 'CREATE', 'Notification Templates', 'Added template: '.$validated['name'], $validated);

        return back()->with('success', 'Template added successfully.');
    }

    public function storeIntegration(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Connected,Disconnected,Issue'],
        ]);

        $integrations = collect($this->config('admin.integrations.items', []));
        $integrations->push($validated);

        $this->putConfig('admin.integrations.items', $integrations->values()->all());
        $this->logAction($request, 'CREATE', 'API Integrations', 'Added integration: '.$validated['name'], $validated);

        return back()->with('success', 'Integration added successfully.');
    }

    public function updateApiKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'key_value' => ['required', 'string', 'max:255'],
        ]);

        $keys = $this->config('admin.integrations.keys', []);
        $keys[$validated['label']] = $validated['key_value'];

        $this->putConfig('admin.integrations.keys', $keys);
        $this->logAction($request, 'UPDATE', 'API Integrations', 'Updated API key: '.$validated['label']);

        return back()->with('success', 'API key saved successfully.');
    }

    public function storeValidationRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'field_label' => ['required', 'string', 'max:255'],
            'field_name' => ['nullable', 'string', 'max:255'],
            'rule_type' => ['nullable', 'string', 'max:255'],
            'rule_value' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $rules = collect($this->config('admin.validation.rules', []));
        if (! $rules->contains($validated['field_label'])) {
            $rules->push($validated['field_label']);
            $this->putConfig('admin.validation.rules', $rules->values()->all());
        }

        $this->logAction($request, 'CREATE', 'Data Validation', 'Added validation rule: '.$validated['field_label'], $validated);

        return back()->with('success', 'Validation rule added successfully.');
    }

    private function baseStats(): array
    {
        // Combine four employee count queries into a single round-trip using
        // PostgreSQL's FILTER clause. The Supabase pooler in Tokyo adds a
        // large per-query latency, so reducing the number of queries is the
        // single biggest win for /admin/dashboard load time.
        $sixMonthsAgo = now()->subMonths(6)->toDateTimeString();
        $fiveMonthsAgo = now()->subMonths(5)->toDateTimeString();

        $row = DB::selectOne(
            'SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = ?) AS active,
                COUNT(*) FILTER (WHERE resume_last_updated_at >= ?) AS valid_credentials,
                COUNT(*) FILTER (WHERE resume_last_updated_at < ?) AS expiring_prc
             FROM employees
             WHERE deleted_at IS NULL',
            ['active', $sixMonthsAgo, $fiveMonthsAgo]
        );

        $totalEmployees = (int) ($row->total ?? 0);
        $activeEmployees = (int) ($row->active ?? 0);
        $validCredentials = (int) ($row->valid_credentials ?? 0);
        $expiringPrc = (int) ($row->expiring_prc ?? 0);

        return [
            'total_employees' => $totalEmployees,
            'active_faculty' => $activeEmployees,
            'compliance_rate' => $totalEmployees > 0 ? round(($validCredentials / $totalEmployees) * 100) : 0,
            'attendance_rate' => $activeEmployees > 0 ? min(80 + $activeEmployees, 99) : 0,
            'expiring_prc' => $expiringPrc,
            'pending_verifications' => max($totalEmployees - $validCredentials, 0),
        ];
    }

    private function recentActivities(): Collection
    {
        $auditActivities = AdminAuditLog::query()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (AdminAuditLog $log) => sprintf(
                '%s %s in %s: %s',
                $log->user?->name ?? 'System',
                strtoupper($log->action),
                $log->module,
                $log->description
            ));

        if ($auditActivities->isNotEmpty()) {
            return $auditActivities;
        }

        $announcementActivities = Announcement::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Announcement $announcement) => ($announcement->creator?->name ?? 'System').' posted "'.$announcement->title.'"');

        if ($announcementActivities->isNotEmpty()) {
            return $announcementActivities;
        }

        return collect(['No recent activity found.']);
    }

    private function roleLabel(?int $userType): string
    {
        return match ($userType) {
            User::TYPE_ADMIN => 'Admin',
            User::TYPE_HR => 'HR Personnel',
            default => 'Employee',
        };
    }

    private function config(string $key, mixed $default): mixed
    {
        $row = AdminConfig::query()->where('key', $key)->first();

        if (! $row) {
            return $default;
        }

        return $row->value ?? $default;
    }

    private function putConfig(string $key, mixed $value): void
    {
        AdminConfig::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    private function logAction(Request $request, string $action, string $module, string $description, array $metadata = []): void
    {
        AdminAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'status' => 'Success',
            'metadata' => $metadata,
        ]);
    }
}
