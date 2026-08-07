<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendarEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicCalendarController extends Controller
{
    public function index(): View
    {
        $entries = AcademicCalendarEntry::query()
            ->orderByDesc('event_date')
            ->orderBy('title')
            ->paginate(12);

        $academicCalendarEntries = AcademicCalendarEntry::query()
            ->orderBy('event_date')
            ->get()
            ->map(fn (AcademicCalendarEntry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'entry_type' => $entry->entry_type,
                'day_type' => $entry->day_type,
                'event_date' => $entry->event_date->toDateString(),
                'description' => $entry->description,
                'type_label' => $entry->type_label,
                'badge_class' => $entry->badge_class,
            ])
            ->values();

        return view('admin.academic-calendar.index', [
            'entries' => $entries,
            'academicCalendarEntries' => $academicCalendarEntries,
            'stats' => [
                'total' => AcademicCalendarEntry::query()->count(),
                'holidays' => AcademicCalendarEntry::query()->where('entry_type', 'holiday')->count(),
                'events' => AcademicCalendarEntry::query()->where('entry_type', 'event')->count(),
                'working' => AcademicCalendarEntry::query()->where('day_type', 'working')->count(),
                'non_working' => AcademicCalendarEntry::query()->where('day_type', 'non_working')->count(),
                'upcoming' => AcademicCalendarEntry::query()->upcoming()->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEntry($request);

        AcademicCalendarEntry::query()->create($validated);

        return redirect()->route('admin.academic-calendar.index')
            ->with('success', 'Academic calendar date added successfully.');
    }

    public function update(Request $request, AcademicCalendarEntry $academicCalendarEntry): RedirectResponse
    {
        $validated = $this->validateEntry($request);

        $academicCalendarEntry->update($validated);

        return redirect()->route('admin.academic-calendar.index')
            ->with('success', 'Academic calendar date updated successfully.');
    }

    public function destroy(AcademicCalendarEntry $academicCalendarEntry): RedirectResponse
    {
        $academicCalendarEntry->delete();

        return redirect()->route('admin.academic-calendar.index')
            ->with('success', 'Academic calendar date deleted successfully.');
    }

    private function validateEntry(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'entry_type' => ['required', 'in:holiday,event'],
            'day_type' => ['required', 'in:working,non_working'],
            'event_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}