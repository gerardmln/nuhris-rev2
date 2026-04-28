@extends('employee.layout')

@section('title', 'Attendance & DTR')
@section('page_title', 'Attendance & DTR')

@section('content')
    <p class="text-sm text-slate-600">View your attendance records, computed metrics, and DTR.</p>

    <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Weekly Schedule</p>
                <h2 class="mt-1 text-3xl font-bold text-slate-900">Submit your Monday to Saturday schedule</h2>
                <p class="mt-1 text-sm text-slate-600">Mark each day as With Work or No Work. HR approval is required before this schedule becomes the DTR reference.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <p class="font-semibold text-slate-800">Current submission</p>
                <p>{{ $currentSchedule?->term_label ?? $currentSchedule?->semester_label ?? 'No submission yet' }}</p>
                <p class="mt-1">Status: <span class="font-semibold">{{ ucfirst($currentSchedule?->status ?? 'draft') }}</span></p>
            </div>
        </div>

        @if ($currentSchedule?->status === 'approved')
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <p class="font-semibold">This schedule is approved and locked.</p>
                <p class="mt-1 text-sm">Please contact HR to request schedule changes.</p>

                <div class="mt-4 grid grid-cols-1 gap-3 xl:grid-cols-2">
                    @foreach ($scheduleDays as $day)
                        @php($savedDay = $scheduleDayMap[$day['label']] ?? null)
                        <div class="rounded-2xl border border-amber-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-bold text-slate-900">{{ $day['label'] }}</h3>
                                <span class="text-xs font-semibold {{ $savedDay?->has_work ? 'text-emerald-700' : 'text-red-700' }}">{{ $savedDay?->has_work ? 'With Work' : 'No Work' }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-700">{{ $savedDay?->has_work ? (($savedDay?->time_in?->format('h:i A') ?? 'N/A').' - '.($savedDay?->time_out?->format('h:i A') ?? 'N/A')) : 'No time inputs' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('employee.attendance.schedule.store') }}" class="mt-6 space-y-4">
                @csrf

                @unless ($canEditSchedule)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        You already have an active schedule submission. The button is disabled until HR reviews it or resets it.
                    </div>
                @endunless

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Term</label>
                    <select
                        name="term_label"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                        required
                    >
                        <option value="" disabled {{ old('term_label', $currentSchedule?->term_label ?? $currentSchedule?->semester_label ?? '') === '' ? 'selected' : '' }}>Select a term</option>
                        @foreach (['1st Term', '2nd Term', '3rd Term'] as $term)
                            <option value="{{ $term }}" {{ old('term_label', $currentSchedule?->term_label ?? $currentSchedule?->semester_label ?? '') === $term ? 'selected' : '' }}>{{ $term }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Choose the term before submitting.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    @foreach ($scheduleDays as $day)
                        @php
                            $savedDay = $scheduleDayMap[$day['label']] ?? null;
                            $dayMode = old("days.{$day['key']}.mode", $savedDay?->has_work ? 'with_work' : 'no_work');
                        @endphp
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4" x-data="{ mode: '{{ $dayMode }}' }">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-bold text-slate-900">{{ $day['label'] }}</h3>
                                <div class="inline-flex rounded-full border border-slate-200 bg-white p-1 text-xs font-semibold">
                                    <button type="button" @click="mode = 'with_work'" :class="mode === 'with_work' ? 'bg-emerald-600 text-white' : 'text-emerald-700'" class="rounded-full px-3 py-1">With Work</button>
                                    <button type="button" @click="mode = 'no_work'" :class="mode === 'no_work' ? 'bg-red-600 text-white' : 'text-red-700'" class="rounded-full px-3 py-1">No Work</button>
                                </div>
                            </div>

                            <input type="hidden" name="days[{{ $day['key'] }}][mode]" x-model="mode">

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2" x-show="mode === 'with_work'" x-cloak>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-700">Time In</label>
                                    <input
                                        type="time"
                                        name="days[{{ $day['key'] }}][time_in]"
                                        value="{{ old("days.{$day['key']}.time_in", $savedDay?->time_in?->format('H:i')) }}"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-700">Time Out</label>
                                    <input
                                        type="time"
                                        name="days[{{ $day['key'] }}][time_out]"
                                        value="{{ old("days.{$day['key']}.time_out", $savedDay?->time_out?->format('H:i')) }}"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                                    >
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-3 py-2 text-xs text-slate-500" x-show="mode === 'no_work'" x-cloak>
                                Time inputs are disabled for no-work days.
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="reset" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" @disabled(! $canEditSchedule) class="rounded-md bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d] disabled:cursor-not-allowed disabled:bg-slate-400">Submit Schedule to HR</button>
                </div>
            </form>
        @endif
    </article>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-700">Tardiness</p>
            <p class="mt-2 text-5xl font-extrabold">{{ $totals['tardiness'] }}m</p>
        </article>
        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-700">Undertime</p>
            <p class="mt-2 text-5xl font-extrabold">{{ $totals['undertime'] }}m</p>
        </article>
        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-700">Overtime</p>
            <p class="mt-2 text-5xl font-extrabold">{{ $totals['overtime'] }}m</p>
        </article>
        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-700">Absences</p>
            <p class="mt-2 text-5xl font-extrabold">{{ $totals['absences'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-slate-700">Workload Credits</p>
            <p class="mt-2 text-5xl font-extrabold">{{ $totals['workload_credits'] }}</p>
        </article>
    </div>

    <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
        <h2 class="text-3xl font-bold text-slate-900">Daily Time Records</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Time In</th>
                        <th class="px-4 py-2">Time Out</th>
                        <th class="px-4 py-2">Scheduled</th>
                        <th class="px-4 py-2">Tardiness</th>
                        <th class="px-4 py-2">Undertime</th>
                        <th class="px-4 py-2">OT</th>
                        <th class="px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-4 py-2">{{ $record['date'] }}</td>
                            <td class="px-4 py-2">{{ $record['time_in'] ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $record['time_out'] ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $record['scheduled'] }}</td>
                            <td class="px-4 py-2">{{ $record['tardiness_minutes'] }}m</td>
                            <td class="px-4 py-2">{{ $record['undertime_minutes'] }}m</td>
                            <td class="px-4 py-2">{{ $record['overtime_minutes'] }}m</td>
                            <td class="px-4 py-2">
                                <div>{{ $record['status'] }}</div>
                                @if (! in_array($record['schedule_status'] ?? '', ['validated', '', null], true))
                                    <div class="text-xs text-slate-500">{{ $record['schedule_notes'] ?? ucfirst(str_replace('_', ' ', $record['schedule_status'])) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-24 text-center text-2xl text-slate-400">No attendance records found this month</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection