@extends('employee.layout')

@section('title', 'Attendance & DTR')
@section('page_title', 'Attendance & DTR')

@section('content')
    <p class="text-sm text-slate-600">View your attendance records, computed metrics, and DTR.</p>

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

        <div class="mt-4 max-w-md">
            <select class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option>All Records</option>
                @foreach ($periods as $period)
                    <option>{{ $period }}</option>
                @endforeach
            </select>
        </div>

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
                            <td class="px-4 py-2">{{ $record['status'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-24 text-center text-2xl text-slate-400">No attendance records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection