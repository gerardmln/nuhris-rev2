<?php

namespace Tests\Feature\Api;

use App\Models\AcademicCalendarEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicCalendarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_style_request_can_fetch_academic_calendar_json(): void
    {
        AcademicCalendarEntry::create([
            'title' => 'University Week',
            'entry_type' => 'event',
            'day_type' => 'working',
            'event_date' => '2026-09-20',
            'description' => 'Week-long university event',
        ]);

        AcademicCalendarEntry::create([
            'title' => 'Foundation Day',
            'entry_type' => 'holiday',
            'day_type' => 'non_working',
            'event_date' => '2026-09-15',
            'description' => 'University Foundation Day',
        ]);

        $response = $this->getJson(route('api.academic-calendar.index'));

        $response->assertOk();
        $response->assertExactJson([
            [
                'id' => 2,
                'title' => 'Foundation Day',
                'entry_type' => 'holiday',
                'day_type' => 'non_working',
                'event_date' => '2026-09-15',
                'description' => 'University Foundation Day',
            ],
            [
                'id' => 1,
                'title' => 'University Week',
                'entry_type' => 'event',
                'day_type' => 'working',
                'event_date' => '2026-09-20',
                'description' => 'Week-long university event',
            ],
        ]);
    }

    public function test_academic_calendar_json_contains_only_allowed_fields(): void
    {
        AcademicCalendarEntry::create([
            'title' => 'Foundation Day',
            'entry_type' => 'holiday',
            'day_type' => 'non_working',
            'event_date' => '2026-09-15',
            'description' => 'University Foundation Day',
        ]);

        $response = $this->getJson(route('api.academic-calendar.index'));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonStructure([
            '*' => ['id', 'title', 'entry_type', 'day_type', 'event_date', 'description'],
        ]);
        $this->assertSame(
            ['id', 'title', 'entry_type', 'day_type', 'event_date', 'description'],
            array_keys($response->json()[0])
        );
    }

    public function test_academic_calendar_json_is_ordered_by_event_date_ascending(): void
    {
        AcademicCalendarEntry::create([
            'title' => 'Late Event',
            'entry_type' => 'event',
            'day_type' => 'working',
            'event_date' => '2026-10-05',
            'description' => 'Later event',
        ]);

        AcademicCalendarEntry::create([
            'title' => 'Early Holiday',
            'entry_type' => 'holiday',
            'day_type' => 'non_working',
            'event_date' => '2026-08-21',
            'description' => 'Earlier holiday',
        ]);

        AcademicCalendarEntry::create([
            'title' => 'Middle Event',
            'entry_type' => 'event',
            'day_type' => 'working',
            'event_date' => '2026-09-10',
            'description' => 'Middle event',
        ]);

        $response = $this->getJson(route('api.academic-calendar.index'));

        $response->assertOk();
        $this->assertSame(
            ['2026-08-21', '2026-09-10', '2026-10-05'],
            array_column($response->json(), 'event_date')
        );
    }
}
