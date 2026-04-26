@extends('admin.layout')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Total Employees</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['total_employees'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Active Faculty</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['active_faculty'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Compliance Rate</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['compliance_rate'] }}%</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Attendance Rate</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['attendance_rate'] }}%</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Expiring PRC</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['expiring_prc'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Pending Verifications</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['pending_verifications'] }}</p>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm xl:col-span-2">
            <h3 class="text-2xl font-bold text-[#24358a]">Attendance Trends</h3>
            <div class="mt-3 h-56 rounded-lg border border-slate-200 bg-slate-50"></div>
        </article>

        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-2xl font-bold text-[#24358a]">Role Distribution</h3>
            <div class="mt-3 h-56 rounded-lg border border-slate-200 bg-slate-50"></div>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-2xl font-bold text-[#24358a]">Compliance Progress by Term</h3>
            <div class="mt-3 h-64 rounded-lg border border-slate-200 bg-slate-50"></div>
        </article>

        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-2xl font-bold text-[#24358a]">Recent System Activity</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @foreach ($recentActivities as $activity)
                    <li>{{ $activity }}</li>
                @endforeach
            </ul>
        </article>
    </div>
@endsection