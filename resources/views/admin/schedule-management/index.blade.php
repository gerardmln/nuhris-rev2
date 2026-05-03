@extends('admin.layout')

@section('title', 'Schedule Management')

@php
    $pageTitle = 'Schedule Management';
    $pageHeading = 'Schedule Management and Reset';
@endphp

@section('content')
    <div class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Employee-submitted schedules</p>
                <h2 class="mt-1 text-3xl font-bold text-slate-900">Schedule Management</h2>
                <p class="mt-1 text-sm text-slate-600">Review submitted weekly schedules, then reset them when the employee needs to resubmit.</p>
            </div>
            <button
                type="button"
                onclick="if(confirm('Reset ALL schedules? This will clear every schedule submission and force employees to resubmit.')) { document.getElementById('reset-all-schedules-form').submit(); }"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
            >
                Reset All Schedules
            </button>
        </div>
    </div>

    <form id="reset-all-schedules-form" action="{{ route('admin.schedules.reset-all') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Total</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-900">{{ $counts['total'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Pending</p>
            <p class="mt-1 text-3xl font-extrabold text-amber-600">{{ $counts['pending'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Approved</p>
            <p class="mt-1 text-3xl font-extrabold text-emerald-700">{{ $counts['approved'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Reset</p>
            <p class="mt-1 text-3xl font-extrabold text-red-600">{{ $counts['reset'] }}</p>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        @forelse ($employeeSchedules as $entry)
            @php
                $employee = $entry['employee'];
                $submission = $entry['submission'];
                $status = $entry['status'];
            @endphp
            <article class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            {{ $status === 'needs_upload' ? 'Needs Upload' : ucfirst($status) }}
                        </p>
                        <h3 class="text-2xl font-bold text-slate-900">{{ $employee->full_name }}</h3>
                        <p class="text-sm text-slate-600">{{ $employee->department?->name ?? 'Unassigned' }}</p>
                    </div>
                    @if ($submission)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Term: {{ $submission->term_label ?? $submission->semester_label }}</span>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Needs to upload schedule</span>
                    @endif
                </div>

                @if ($submission)
                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach ($submission->days as $day)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold text-slate-900">{{ $day->day_name }}</p>
                                    <span class="text-xs font-semibold {{ $day->has_work ? 'text-emerald-700' : 'text-red-700' }}">{{ $day->has_work ? 'With Work' : 'No Work' }}</span>
                                </div>
                                <p class="mt-1 text-slate-600">
                                    {{ $day->has_work ? ($day->time_in?->format('h:i A') ?? 'N/A').' - '.($day->time_out?->format('h:i A') ?? 'N/A') : 'No time inputs' }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @if ($entry['schedule_summary'] ?? null)
                        <p class="mt-4 text-xs text-slate-500">Approved schedule summary: {{ $entry['schedule_summary'] }}</p>
                    @endif

                    @if ($submission->review_notes)
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Review Notes</p>
                            <p class="mt-1">{{ $submission->review_notes }}</p>
                        </div>
                    @endif
                @else
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        This employee has no active schedule submission. Please ask them to upload a weekly schedule.
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @if ($submission && $submission->status === 'pending')
                        <form method="POST" action="{{ route('admin.schedules.approve', $submission) }}" onsubmit="return confirm('Approve this schedule?');">
                            @csrf
                            <input type="hidden" name="review_notes" value="Approved by Admin">
                            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                Approve Schedule
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.schedules.employee.reset', $employee) }}" onsubmit="return confirm('Reset schedule for this employee?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                            Reset Employee Schedule
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 xl:col-span-2">
                No employees found.
            </div>
        @endforelse
    </div>
@endsection