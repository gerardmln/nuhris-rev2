<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Mail\EmployeeCredentialsMail;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
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

        $employeesQuery = Employee::query()
            ->with(['department'])
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($nested) use ($searchTerm) {
                    $nested
                        ->where('employee_id', 'like', "%{$searchTerm}%")
                        ->orWhere('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($departmentId === 'asp') {
            $employeesQuery->where(function ($query) {
                $query->where('employment_type', 'Admin Support Personnel');
            });
        } elseif (filled($departmentId)) {
            $employeesQuery->where('department_id', $departmentId);
        }

        $employees = $employeesQuery->paginate(15)->withQueryString();

        return view('admin.employees.index', [
            'employees' => $employees,
            'departments' => Department::query()->orderBy('name')->get(),
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
            ],
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('admin.employees.edit', array_merge([
            'employee' => $employee,
            'departments' => Department::query()->orderBy('name')->get(),
        ], $this->formOptions()));
    }

    public function show(Employee $employee): View
    {
        return view('admin.employees.show', [
            'employee' => $employee->load('department'),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $payload = $request->validated();
        $payload = $this->applyDefaultDepartmentForNonTeachingRoles($payload);
        $previousEmail = $employee->email;
        $employee->update($payload);

        User::query()->where('email', $previousEmail)->update(['email' => $employee->email, 'name' => $employee->full_name]);

        return redirect()->route('admin.employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $fullName = $employee->full_name;
        $email = $employee->email;

        try {
            DB::transaction(function () use ($employee, $email) {
                User::query()->where('email', $email)->delete();
                $employee->forceDelete();
            });
        } catch (\Throwable $exception) {
            Log::error('Failed to delete employee (admin)', ['employee_id' => $employee->id, 'error' => $exception->getMessage()]);

            return redirect()->route('admin.employees.index')->with('error', 'Failed to delete '.$fullName.': '.$exception->getMessage());
        }

        return redirect()->route('admin.employees.index')->with('success', $fullName.' deleted.');
    }

    public function resendCredentials(Employee $employee): RedirectResponse
    {
        [$tempPassword] = $this->provisionEmployeeAccount($employee, forceReset: true);
        $emailStatus = $this->sendCredentialsEmail($employee, $tempPassword, isResend: true);

        return redirect()->route('admin.employees.index')->with('success', 'Credentials regenerated for '.$employee->full_name)->with('credential_notice', [
            'employee_name' => $employee->full_name,
            'employee_id' => $employee->employee_id,
            'email' => $employee->email,
            'temp_password' => $tempPassword,
            'is_resend' => true,
            'email_status' => $emailStatus,
        ]);
    }

    private function sendCredentialsEmail(Employee $employee, string $tempPassword, bool $isResend): array
    {
        try {
            Mail::to($employee->email)->send(new EmployeeCredentialsMail($employee, $tempPassword, $isResend));
            return ['sent' => true, 'message' => null];
        } catch (\Throwable $exception) {
            Log::warning('Admin: Failed to send credentials email', ['employee_id' => $employee->id, 'error' => $exception->getMessage()]);
            return ['sent' => false, 'message' => $exception->getMessage()];
        }
    }

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
                $user->forceFill(['name' => $employee->full_name, 'password' => Hash::make($tempPassword)])->save();
            }
        });

        return [$tempPassword];
    }

    /**
     * Shared dropdown options for the employee edit screen.
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
        $employmentType = Str::lower((string) ($payload['employment_type'] ?? ''));

        if (Str::contains($employmentType, 'faculty')) {
            return $payload;
        }

        $position = Str::lower((string) ($payload['position'] ?? ''));
        $facultyPositions = array_map(
            fn ($p) => Str::lower((string) $p),
            (array) config('hris.faculty_positions', [])
        );

        if (in_array($position, $facultyPositions, true)) {
            return $payload;
        }

        $aspDepartmentId = Department::query()->where('name', 'ASP')->value('id');

        if (filled($aspDepartmentId)) {
            $payload['department_id'] = $aspDepartmentId;
        }

        return $payload;
    }
}
