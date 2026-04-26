<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Database\Seeder;

class EmployeePortalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departmentId = Department::query()->where('name', 'SACE - School of Architecture, Computing and Engineering')->value('id')
            ?? Department::query()->value('id');

        $employee = Employee::updateOrCreate([
            'email' => 'test101EMP@gmail.com',
        ], [
            'employee_id' => 'NU-EMP-0001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'phone' => '09170000001',
            'address' => 'NU Lipa Campus',
            'department_id' => $departmentId,
            'position' => 'Instructor',
            'employment_type' => 'Faculty',
            'ranking' => 'Instructor 1',
            'status' => 'active',
            'hire_date' => now()->subYears(2)->toDateString(),
            'official_time_in' => '08:30:00',
            'official_time_out' => '17:30:00',
            'resume_last_updated_at' => now()->subMonths(2)->toDateString(),
        ]);

        EmployeeCredential::updateOrCreate([
            'employee_id' => $employee->id,
            'credential_type' => 'resume',
            'title' => 'Professional Resume',
        ], [
            'department_id' => $departmentId,
            'status' => 'verified',
            'expires_at' => null,
        ]);

        EmployeeCredential::updateOrCreate([
            'employee_id' => $employee->id,
            'credential_type' => 'prc',
            'title' => 'PRC License 2026',
        ], [
            'department_id' => $departmentId,
            'status' => 'pending',
            'expires_at' => now()->addMonths(7)->toDateString(),
        ]);

        LeaveBalance::updateOrCreate([
            'employee_id' => $employee->id,
            'leave_type' => 'Vacation Leave',
        ], [
            'remaining_days' => 12,
        ]);

        LeaveBalance::updateOrCreate([
            'employee_id' => $employee->id,
            'leave_type' => 'Sick Leave',
        ], [
            'remaining_days' => 10,
        ]);

        LeaveRequest::updateOrCreate([
            'employee_id' => $employee->id,
            'leave_type' => 'Sick Leave',
            'start_date' => now()->subDays(20)->toDateString(),
        ], [
            'end_date' => now()->subDays(19)->toDateString(),
            'days_deducted' => 2,
            'status' => 'approved',
            'cutoff_date' => now()->subDays(15)->toDateString(),
            'reason' => 'Medical rest',
        ]);

        AttendanceRecord::updateOrCreate([
            'employee_id' => $employee->id,
            'record_date' => now()->subDays(2)->toDateString(),
        ], [
            'time_in' => '08:35:00',
            'time_out' => '17:40:00',
            'scheduled_time_in' => '08:30:00',
            'scheduled_time_out' => '17:30:00',
            'tardiness_minutes' => 5,
            'undertime_minutes' => 0,
            'overtime_minutes' => 10,
            'status' => 'present',
        ]);

        AttendanceRecord::updateOrCreate([
            'employee_id' => $employee->id,
            'record_date' => now()->subDay()->toDateString(),
        ], [
            'time_in' => null,
            'time_out' => null,
            'scheduled_time_in' => '08:30:00',
            'scheduled_time_out' => '17:30:00',
            'tardiness_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
            'status' => 'absent',
        ]);
    }
}
