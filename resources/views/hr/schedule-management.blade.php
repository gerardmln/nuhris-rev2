@extends('hr.layout')

@php
    $pageTitle = 'Schedule Management';
    $pageHeading = 'Schedule Management and Approval';
    $activeNav = 'schedules';
@endphp

@section('content')
    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Employee-submitted schedules</p>
                <h2 class="mt-1 text-3xl font-bold text-slate-900">Schedule Management</h2>
                <p class="mt-1 text-sm text-slate-600">Review employee-submitted weekly schedules before they become the attendance reference.</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('schedules.index') }}" class="grid gap-2 md:grid-cols-4">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search employees" class="w-full rounded-md border border-slate-300 px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 md:col-span-2">
            <select name="department_id" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="all" @selected(($filters['department_id'] ?? 'all') === 'all')>All Departments</option>
                <option value="asp" @selected(($filters['department_id'] ?? '') === 'asp')>Admin Support Personnel</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Statuses</option>
                <option value="needs_upload" @selected(($filters['status'] ?? '') === 'needs_upload')>Needs Upload</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                <option value="declined" @selected(($filters['status'] ?? '') === 'declined')>Declined</option>
                <option value="reset" @selected(($filters['status'] ?? '') === 'reset')>Reset</option>
            </select>
        </form>
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
                        <p class="text-sm text-slate-600">
                            {{ $employee->department?->name ?? 'Unassigned' }}
                            @if ($submission)
                                · Submitted by {{ $submission->submitter?->name ?? 'Employee' }}
                            @endif
                        </p>
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
                @else
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        This employee has no active schedule submission. Please ask them to upload a weekly schedule.
                    </div>
                @endif

                @if ($submission && $submission->review_notes)
                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Review Notes</p>
                        <p class="mt-1">{{ $submission->review_notes }}</p>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @if ($submission && $submission->status === 'pending')
                        <form method="POST" action="{{ route('schedules.approve', $submission) }}" onsubmit="return confirm('Approve this schedule?');">
                            @csrf
                            <input type="hidden" name="confirmed" value="1">
                            <input type="hidden" name="review_notes" value="Approved by HR">
                            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Accept</button>
                        </form>

                        <form method="POST" action="{{ route('schedules.decline', $submission) }}" onsubmit="return confirm('Decline this schedule?');" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="confirmed" value="1">
                            <input type="text" name="review_notes" placeholder="Reason for decline" class="rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 shadow-sm ring-1 ring-red-200">Decline</button>
                        </form>
                    @elseif ($submission && $submission->status === 'approved')
                        <form method="POST" action="{{ route('schedules.clear', $submission) }}" onsubmit="return confirm('Clear this approved schedule? The employee will need to resubmit.');">
                            @csrf
                            <input type="hidden" name="confirmed" value="1">
                            <input type="hidden" name="review_notes" value="Cleared by HR">
                            <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Clear</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 xl:col-span-2">
                No employees found.
            </div>
        @endforelse
    </div>
@endsection