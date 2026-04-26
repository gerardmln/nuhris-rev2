<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();

        $employees = [
            [
                'employee_id' => 'EMP001',
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => 'juan.delacruz@nu.edu.ph',
                'phone' => '09171234567',
                'position' => 'Assistant Professor',
                'employment_type' => 'Faculty',
                'ranking' => 'Assistant Professor 1',
                'status' => 'active',
                'hire_date' => now()->subYears(3),
                'official_time_in' => '08:00',
                'official_time_out' => '17:00',
            ],
            [
                'employee_id' => 'EMP002',
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@nu.edu.ph',
                'phone' => '09181234567',
                'position' => 'Associate Professor',
                'employment_type' => 'Faculty',
                'ranking' => 'Associate Professor 2',
                'status' => 'active',
                'hire_date' => now()->subYears(2),
                'official_time_in' => '08:00',
                'official_time_out' => '17:00',
            ],
            [
                'employee_id' => 'EMP003',
                'first_name' => 'Pedro',
                'last_name' => 'Garcia',
                'email' => 'pedro.garcia@nu.edu.ph',
                'phone' => '09191234567',
                'position' => 'Admissions Office',
                'employment_type' => 'Admin Support Personnel',
                'ranking' => null,
                'status' => 'active',
                'hire_date' => now()->subYears(1),
                'official_time_in' => '09:00',
                'official_time_out' => '17:00',
            ],
            [
                'employee_id' => 'EMP004',
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'email' => 'ana.reyes@nu.edu.ph',
                'phone' => '09201234567',
                'position' => 'Human Resource Office (HR)',
                'employment_type' => 'Admin Support Personnel',
                'ranking' => null,
                'status' => 'active',
                'hire_date' => now()->subMonths(6),
                'official_time_in' => '08:00',
                'official_time_out' => '17:00',
            ],
            [
                'employee_id' => 'EMP005',
                'first_name' => 'Robert',
                'last_name' => 'Cruz',
                'email' => 'robert.cruz@nu.edu.ph',
                'phone' => '09211234567',
                'position' => 'Information Technology Systems Office / Information Technology Services Office',
                'employment_type' => 'Admin Support Personnel',
                'ranking' => null,
                'status' => 'active',
                'hire_date' => now()->subMonths(3),
                'official_time_in' => '08:30',
                'official_time_out' => '17:30',
            ],
        ];

        foreach ($employees as $index => $employeeData) {
            $department = $departments->get($index % count($departments));

            $employee = Employee::withTrashed()->firstOrNew([
                'employee_id' => $employeeData['employee_id'],
            ]);

            $employee->fill([
                ...$employeeData,
                'department_id' => $department->id,
                'deleted_at' => null,
            ]);

            $employee->save();
        }
    }
}
