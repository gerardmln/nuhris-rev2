@extends('employee.layout')

@section('title', 'WFH Monitoring')
@section('page_title', 'WFH Monitoring')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="max-w-3xl text-sm text-slate-600">Upload your Work Output Monitoring Sheet for a WFH day. Once HR approves it, the approved date is written to your attendance record.</p>
        <a href="{{ route('employee.wfh-monitoring.upload') }}"
           class="rounded-xl bg-[#003a78] px-5 py-2 text-sm font-semibold text-white hover:bg-[#002f61]"
           data-testid="employee-wfh-upload-new">+ Upload New</a>
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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">WFH Monitoring Submissions</h2>
                <p class="mt-1 text-sm text-slate-500">Approved entries will create or update your attendance record for the selected WFH date.</p>
            </div>
        </div>

        @if ($submissions->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                <p class="text-lg font-semibold text-slate-600">No WFH monitoring sheets uploaded yet.</p>
                <p class="mt-2 text-sm text-slate-500">Upload your first Work Output Monitoring Sheet to start the review flow.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Time In</th>
                            <th class="px-4 py-3">Time Out</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Reviewed</th>
                            <th class="px-4 py-3">File</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($submissions as $submission)
                            <tr class="align-top">
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ $submission['date'] }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $submission['time_in'] }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $submission['time_out'] }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $submission['status_class'] }}">{{ $submission['status_label'] }}</span>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div>{{ $submission['reviewed_at'] }}</div>
                                    @if (! empty($submission['review_notes']))
                                        <div class="mt-1 max-w-sm text-xs text-slate-500">{{ $submission['review_notes'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($submission['has_file'])
                                        <a href="{{ route('employee.wfh-monitoring.view', $submission['id']) }}"
                                           class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                           data-testid="employee-wfh-view-file-{{ $submission['id'] }}">View file</a>
                                    @else
                                        <span class="text-slate-400">—</span>
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
