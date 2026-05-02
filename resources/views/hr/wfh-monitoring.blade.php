@extends('hr.layout')

@php
    $pageTitle = 'WFH Monitoring';
    $pageHeading = 'WFH Monitoring';
    $activeNav = 'wfh-monitoring';
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="max-w-3xl text-sm text-slate-600">Review submitted Work Output Monitoring Sheets. Approving a sheet will create or update the employee's attendance record for the submitted WFH date.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['all'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pending</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-800">{{ $stats['pending'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Approved</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-800">{{ $stats['approved'] }}</p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Declined</p>
            <p class="mt-2 text-3xl font-extrabold text-rose-800">{{ $stats['declined'] }}</p>
        </div>
    </div>

    <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
        <h2 class="text-3xl font-bold text-[#1f2b5d]">WFH Review Queue</h2>
        <p class="mt-1 text-sm text-slate-500">Pending approvals create the attendance record immediately when approved.</p>

        @if ($submissions->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center mt-5">
                <p class="text-lg font-semibold text-slate-600">No WFH monitoring sheets submitted yet.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Employee</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Time In</th>
                            <th class="px-4 py-3">Time Out</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Submitted</th>
                            <th class="px-4 py-3">File</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($submissions as $submission)
                            <tr class="align-top">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $submission['employee_name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $submission['department'] }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-700">{{ $submission['date'] }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $submission['time_in'] }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $submission['time_out'] }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $submission['status_class'] }}">{{ $submission['status_label'] }}</span>
                                    @if ($submission['reviewed_at'] !== '—')
                                        <div class="mt-2 text-xs text-slate-500">Reviewed: {{ $submission['reviewed_at'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $submission['submitted_at'] }}</td>
                                <td class="px-4 py-4">
                                    @if ($submission['has_file'])
                                        <a href="{{ route('wfh-monitoring.view', $submission['id']) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View file</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($submission['status'] === 'pending')
                                        <div class="space-y-2">
                                            <form method="POST" action="{{ route('wfh-monitoring.approve', $submission['id']) }}" onsubmit="return confirm('Approve this WFH submission and update attendance?');">
                                                @csrf
                                                <input type="hidden" name="confirmed" value="1">
                                                <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Approve</button>
                                            </form>

                                            <form method="POST" action="{{ route('wfh-monitoring.decline', $submission['id']) }}" onsubmit="return confirm('Decline this WFH submission?');" class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                @csrf
                                                <input type="hidden" name="confirmed" value="1">
                                                <textarea name="review_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs" placeholder="Reason for decline" required></textarea>
                                                @error('review_notes')
                                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                                <button type="submit" class="w-full rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 shadow-sm ring-1 ring-red-200">Decline</button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="max-w-xs text-xs text-slate-500">
                                            @if (! empty($submission['review_notes']))
                                                {{ $submission['review_notes'] }}
                                            @else
                                                No review notes.
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
@endsection
