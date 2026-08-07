<?php

namespace Tests\Feature\Employee;

use App\Models\AcademicCalendarEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDashboardAcademicCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_dashboard_shows_viewable_academic_calendar(): void
    {
        $employeeUser = User::factory()->create([
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'user_type' => User::TYPE_EMPLOYEE,
        ]);

        AcademicCalendarEntry::create([
            'title' => 'Foundation Day',
            'entry_type' => 'holiday',
            'day_type' => 'non_working',
            'event_date' => now()->addDays(7)->toDateString(),
            'description' => 'Holiday',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('employee.dashboard'));

        $response->assertOk();
        $response->assertSee('Open Calendar');
        $response->assertSee('Academic Calendar');
        $response->assertSee('Foundation Day');
    }
}
