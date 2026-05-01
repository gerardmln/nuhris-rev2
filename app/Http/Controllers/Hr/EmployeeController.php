<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Mail\EmployeeCredentialsMail;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\LeaveMonitoringService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $departmentId = $request->string('department_id')->toString();
        $employeeClass = $request->string('employee_class')->toString() ?: 'all';
        $leaveMonitoringService = app(LeaveMonitoringService::class);

        $employeesQuery = Employee::query()
            ->with(['department', 'latestResumeCredential'])
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($nested) use ($searchTerm) {
                    $nested
                        ->where('employee_id', 'like', "%{$searchTerm}%")
                        ->orWhere('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($departmentId === 'asp') {
            $employeesQuery->where(function ($query) {
                $query
                    ->where('employment_type', 'Admin Support Personnel')
                    ->orWhereHas('department', fn ($department) => $department->where('name', 'ASP'));
            });
        } elseif (filled($departmentId)) {
            $employeesQuery->where('department_id', $departmentId);
        }

        $leaveMonitoringService->applyEmployeeClassFilter($employeesQuery, $employeeClass);

        $employees = $employeesQuery
            ->paginate(10)
            ->withQueryString();

        return view('hr.employees', array_merge([
            'employees' => $employees,
            'departments' => Department::query()->facultySchools()->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'employee_class' => $employeeClass,
            ],
        ], $this->formOptions()));
    }

    public function create(): View
    {
        return view('hr.employees.create', array_merge([
            'departments' => Department::query()->facultySchools()->orderBy('name')->get(),
        ], $this->formOptions()));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload = $this->applyDefaultDepartmentForNonTeachingRoles($payload);

        // Force status=active when creating.
        $payload['status'] = 'active';

        // If employee_id is empty/null, the model's `creating` event will
        // auto-generate one using generateEmployeeId(). If it's provided
        // (via "Add Existing Employee"), it will be used as-is.
        if (empty($payload['employee_id'])) {
            unset($payload['employee_id']);
        }

        $attempt = 0;
        $employee = null;

        while (true) {
            try {
                $employee = Employee::create($payload);
                break;
            } catch (UniqueConstraintViolationException $exception) {
                $attempt++;

                if ($attempt >= 3 || ! str_contains($exception->getMessage(), 'employees_employee_id_unique')) {
                    throw $exception;
                }

                // Force a fresh ID when two requests try to create a row at the same time.
                $payload['employee_id'] = Employee::generateEmployeeId();
            }
        }

        // --- Credential Management: provision a login account for the new employee ---
        [$tempPassword] = $this->provisionEmployeeAccount($employee);
        $emailStatus = $this->sendCredentialsEmail($employee, $tempPassword, isResend: false);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee created successfully.')
            ->with('credential_notice', [
                'employee_name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'email' => $employee->email,
                'temp_password' => $tempPassword,
                'email_status' => $emailStatus,
            ]);
    }

    public function show(Employee $employee): View
    {
        $employee->load(['department', 'latestResumeCredential']);

        return view('hr.employees.show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('hr.employees.edit', array_merge([
            'employee' => $employee,
            'departments' => Department::query()->facultySchools()->orderBy('name')->get(),
        ], $this->formOptions()));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $payload = $request->validated();
        $payload = $this->applyDefaultDepartmentForNonTeachingRoles($payload);

        $previousEmail = $employee->email;
        $employee->update($payload);

        // Keep the linked User row aligned with employee profile changes
        // without touching whatever password the employee currently uses.
        User::query()
            ->where('email', $previousEmail)
            ->update([
                'email' => $employee->email,
                'name' => $employee->full_name,
            ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $fullName = $employee->full_name;
        $email = $employee->email;

        try {
            DB::transaction(function () use ($employee, $email) {
                // Hard delete the linked user account (by email).
                // This cascades to announcement_notifications via FK cascadeOnDelete.
                User::query()->where('email', $email)->delete();

                // Hard delete the employee row itself. Cascades configured in
                // migrations will also remove employee_credentials,
                // attendance_records, leave_balances, and leave_requests.
                $employee->forceDelete();
            });
        } catch (\Throwable $exception) {
            Log::error('Failed to delete employee', [
                'employee_id' => $employee->id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('employees.index')
                ->with('error', 'Failed to delete '.$fullName.': '.$exception->getMessage());
        }

        return redirect()
            ->route('employees.index')
            ->with('success', $fullName.' and all related records have been permanently deleted.');
    }

    /**
     * Regenerate a temporary password for an existing employee account and
     * surface it to HR so they can re-share the credentials with the employee.
     */
    public function resendCredentials(Employee $employee): RedirectResponse
    {
        [$tempPassword] = $this->provisionEmployeeAccount($employee, forceReset: true);
        $emailStatus = $this->sendCredentialsEmail($employee, $tempPassword, isResend: true);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Credentials regenerated for '.$employee->full_name.'.')
            ->with('credential_notice', [
                'employee_name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'email' => $employee->email,
                'temp_password' => $tempPassword,
                'is_resend' => true,
                'email_status' => $emailStatus,
            ]);
    }

    /**
     * Send the credentials email via the configured mailer (see config/mail.php).
     * We log & swallow any delivery failure so the HR workflow can still show
     * the password banner as a manual-fallback channel.
     *
     * @return array{sent:bool,message:?string}
     */
    private function sendCredentialsEmail(Employee $employee, string $tempPassword, bool $isResend): array
    {
        try {
            Mail::to($employee->email)
                ->send(new EmployeeCredentialsMail($employee, $tempPassword, $isResend));

            return ['sent' => true, 'message' => null];
        } catch (\Throwable $exception) {
            Log::warning('Failed to send employee credentials email', [
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'error' => $exception->getMessage(),
            ]);

            return ['sent' => false, 'message' => $exception->getMessage()];
        }
    }

    /**
     * Create or refresh the linked User login account for an Employee.
     *
     * @return array{0:string} Plain-text temporary password (shown once to HR).
     */
    private function provisionEmployeeAccount(Employee $employee, bool $forceReset = false): array
    {
        $tempPassword = Str::upper(Str::random(4)).'-'.random_int(1000, 9999);

        DB::transaction(function () use ($employee, $tempPassword, $forceReset) {
            $user = User::query()->where('email', $employee->email)->first();

            if (! $user) {
                User::create([
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'password' => Hash::make($tempPassword),
                    'user_type' => User::TYPE_EMPLOYEE,
                ]);

                return;
            }

            if ($forceReset) {
                $user->forceFill([
                    'name' => $employee->full_name,
                    'password' => Hash::make($tempPassword),
                ])->save();
            }
        });

        return [$tempPassword];
    }

    /**
     * Shared dropdown options for the employee add / edit screens.
     *
     * @return array<string,mixed>
     */
    private function formOptions(): array
    {
        $facultyPositions = array_values(config('hris.faculty_positions', []));
        $aspPositions = array_values(config('hris.admin_support_offices', []));

        return [
            'employmentTypes' => config('hris.employment_types', []),
            'facultyPositions' => $facultyPositions,
            'aspPositions' => $aspPositions,
            'employeePositions' => array_values(array_unique(array_merge($facultyPositions, $aspPositions))),
            'facultyRankings' => config('hris.faculty_rankings', []),
        ];
    }

    private function applyDefaultDepartmentForNonTeachingRoles(array $payload): array
    {
        // If this is a Faculty employment type, trust whatever school /
        // department the HR picked (SABM, SAHS, SACE, SHS, or ASP). All
        // faculty positions — Instructor, Assistant/Associate/Full
        // Professor, Dean, Program Chair — belong to a school, not to ASP.
        $employmentType = Str::lower((string) ($payload['employment_type'] ?? ''));

        if (Str::contains($employmentType, 'faculty')) {
            return $payload;
        }

        // Fallback: also treat any position explicitly listed under
        // hris.faculty_positions as teaching, in case employment_type was
        // omitted from the payload.
        $position = Str::lower((string) ($payload['position'] ?? ''));
        $facultyPositions = array_map(
            fn ($p) => Str::lower((string) $p),
            (array) config('hris.faculty_positions', [])
        );

        if (in_array($position, $facultyPositions, true)) {
            return $payload;
        }

        // Otherwise it's an Admin Support role — force department to ASP.
        $aspDepartmentId = Department::query()->where('name', 'ASP')->value('id');

        if (filled($aspDepartmentId)) {
            $payload['department_id'] = $aspDepartmentId;
        }

        return $payload;
    }
}
