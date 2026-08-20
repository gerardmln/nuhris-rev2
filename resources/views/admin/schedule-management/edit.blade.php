@extends('admin.layout')

@section('title', 'Edit Schedule')

@php
    $pageTitle = 'Edit Schedule';
    $pageHeading = 'Edit Employee Schedule';
@endphp

@section('content')
    <div class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $employee->full_name }}</h2>
                <p class="text-sm text-slate-600">{{ $employee->department?->name ?? 'Unassigned' }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $submission->term_label ?? $submission->semester_label }} {{ $submission->academic_year }}</p>
            </div>
            <a href="{{ route('admin.schedules.index') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Could not save the schedule. Please fix the following:</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.schedules.update', $submission) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @php
                $daysByName = $days->keyBy('day_name');
            @endphp

            @foreach ($weekDays as $day)
                @php
                    $dayData = $daysByName->get($day['label']);
                    $rawHasWork = old("days.{$day['index']}.has_work", $dayData?->has_work ? '1' : '0');
                    $hasWork = in_array((string) $rawHasWork, ['1', 'true', 'on', 'yes'], true);
                    $timeIn = old("days.{$day['index']}.time_in", $dayData?->time_in?->format('H:i') ?? '');
                    $timeOut = old("days.{$day['index']}.time_out", $dayData?->time_out?->format('H:i') ?? '');
                @endphp
                <div class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm" data-day-card>
                    <h3 class="font-semibold text-slate-900">{{ $day['label'] }}</h3>

                    <input type="hidden" name="days[{{ $day['index'] }}][day_name]" value="{{ $day['label'] }}">
                    <input type="hidden" name="days[{{ $day['index'] }}][day_index]" value="{{ $day['index'] }}">
                    <input type="hidden" name="days[{{ $day['index'] }}][has_work]" value="0">

                    <div class="mt-4 space-y-3">
                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                name="days[{{ $day['index'] }}][has_work]"
                                value="1"
                                @checked($hasWork)
                                data-has-work-toggle
                                class="rounded border-slate-300 text-blue-600"
                            >
                            <span class="text-sm font-medium text-slate-700">With Work</span>
                        </label>

                        <div class="space-y-2" data-day-times style="{{ $hasWork ? '' : 'display: none;' }}">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600">Time In</label>
                                <input
                                    type="time"
                                    name="days[{{ $day['index'] }}][time_in]"
                                    value="{{ $timeIn }}"
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-600">Time Out</label>
                                <input
                                    type="time"
                                    name="days[{{ $day['index'] }}][time_out]"
                                    value="{{ $timeOut }}"
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between rounded-xl border border-slate-300 bg-slate-50 p-4">
            <p class="text-sm text-slate-600">Changes will be saved and the employee will be notified.</p>
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white shadow-md ring-1 ring-inset ring-[#00386f] hover:bg-[#002f5d]">
                Save & Notify Employee
            </button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-day-card]').forEach((card) => {
            const toggle = card.querySelector('[data-has-work-toggle]');
            const times = card.querySelector('[data-day-times]');
            if (!toggle || !times) return;

            toggle.addEventListener('change', () => {
                times.style.display = toggle.checked ? '' : 'none';
            });
        });
    </script>
@endsection
