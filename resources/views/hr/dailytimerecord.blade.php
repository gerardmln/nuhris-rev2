<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daily Time Record | {{ config('app.name', 'NU HRIS') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased">
    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6">
        <a href="{{ route('timekeeping.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900" data-testid="back-to-timekeeping">
            <span>&larr;</span>
            Back to Time Keeping
        </a>

        <article class="rounded-xl border border-slate-300 bg-[#cfe1f5] p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-[#1f2b5d]" data-testid="dtr-employee-name">{{ $employee?->full_name ?? 'Employee' }}</h1>
                    <p class="text-sm text-slate-600">{{ $employee?->department?->name ?? 'Unassigned' }} | Period: {{ $period_label }}</p>
                    <p class="text-xs text-slate-600">Approved Schedule: {{ $schedule_summary }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    {{-- Period Selector --}}
                    <form method="GET" action="{{ route('timekeeping.dtr') }}" class="flex items-center gap-2" data-testid="dtr-period-form">
                        <input type="hidden" name="employee" value="{{ $employee?->id }}">
                        <select name="period" onchange="this.form.querySelector('[name=month]').value=this.value.split('-')[0]; this.form.querySelector('[name=year]').value=this.value.split('-')[1]; this.form.submit();"
                                data-testid="dtr-period-selector"
                                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none">
                            @foreach ($periods as $period)
                                <option value="{{ $period['month'] }}-{{ $period['year'] }}" {{ $period['selected'] ? 'selected' : '' }}>
                                    {{ $period['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                    </form>
                </div>
            </div>
        </article>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm" data-testid="present-days-card">
                <p class="text-xs text-slate-500">Present Days</p>
                <p class="text-3xl font-extrabold text-emerald-700">{{ $summary['present_days'] }}</p>
            </article>
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm" data-testid="tardiness-card">
                <p class="text-xs text-slate-500">Total Tardiness</p>
                <p class="text-3xl font-extrabold text-amber-600">{{ $summary['tardiness_total'] }} min</p>
            </article>
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm" data-testid="undertime-card">
                <p class="text-xs text-slate-500">Total Undertime</p>
                <p class="text-3xl font-extrabold text-violet-600">{{ $summary['undertime_total'] }} min</p>
            </article>
        </div>

        {{-- Export Buttons --}}
        <div class="flex flex-wrap gap-2" data-testid="export-buttons">
            <a href="{{ route('timekeeping.dtr.export-pdf', ['employee' => $employee?->id, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
               data-testid="export-pdf-button"
               class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
            <a href="{{ route('timekeeping.dtr.export-excel', ['employee' => $employee?->id, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
               data-testid="export-excel-button"
               class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Export Excel
            </a>
        </div>

        <article class="overflow-x-auto rounded-xl border border-slate-300 bg-white p-4 shadow-sm" data-testid="dtr-table-container">
            <table class="min-w-full text-left text-sm" data-testid="dtr-table">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Day</th>
                        <th class="px-3 py-2">Time In</th>
                        <th class="px-3 py-2">Time Out</th>
                        <th class="px-3 py-2">Tardiness</th>
                        <th class="px-3 py-2">Undertime</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($records as $record)
                        <tr class="{{ $record['status'] === 'Weekend' ? 'bg-slate-50 text-slate-400' : ($record['status'] === 'Non-working day' ? 'bg-amber-50/40' : ($record['status'] === 'Absent' ? 'bg-red-50/40' : '')) }}">
                            <td class="px-3 py-2">{{ $record['date'] }}</td>
                            <td class="px-3 py-2">{{ $record['day'] }}</td>
                            <td class="px-3 py-2">{{ $record['time_in'] }}</td>
                            <td class="px-3 py-2">{{ $record['time_out'] }}</td>
                            <td class="px-3 py-2">{{ $record['tardiness_minutes'] ? $record['tardiness_minutes'].' min' : '-' }}</td>
                            <td class="px-3 py-2">{{ $record['undertime_minutes'] ? $record['undertime_minutes'].' min' : '-' }}</td>
                            <td class="px-3 py-2">
                                @if($record['status'] === 'Present')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Present</span>
                                @elseif($record['status'] === 'Absent')
                                    <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Absent</span>
                                @elseif($record['status'] === 'Non-working day')
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Non-working day</span>
                                @elseif($record['status'] === 'Weekend')
                                    <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-500">Weekend</span>
                                @else
                                    <span class="text-slate-400">{{ $record['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </article>
    </main>
</body>
</html>
