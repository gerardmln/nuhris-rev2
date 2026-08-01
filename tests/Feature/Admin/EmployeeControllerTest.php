<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_employee_create_stores_the_provided_employee_id(): void
    {
        $admin = User::factory()->create(['user_type' => User::TYPE_ADMIN]);
        $department = Department::create(['name' => 'ASP']);

        Mail::fake();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.employees.store'), [
                'employee_id' => 'ADM-2026-001',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@example.com',
                'employment_type' => 'Admin Support Personnel',
                'position' => 'Asset Management Office',
                'department_id' => $department->id,
            ]);

        $response->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('employees', [
            'employee_id' => 'ADM-2026-001',
            'email' => 'jane.doe@example.com',
        ]);
    }

    public function test_admin_employee_create_requires_employee_id(): void
    {
        $admin = User::factory()->create(['user_type' => User::TYPE_ADMIN]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.employees.create'))
            ->post(route('admin.employees.store'), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@example.com',
                'employment_type' => 'Admin Support Personnel',
                'position' => 'Asset Management Office',
            ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseMissing('employees', ['email' => 'jane.doe@example.com']);
    }
}