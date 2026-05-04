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
        <article class="rounded-xl border border-dashed border-[#ffdc00] bg-[#fff9d6] p-4 shadow-sm sm:col-span-2 xl:col-span-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.18em] text-[#7a6500]">Module</p>
                    <p class="mt-1 text-2xl font-extrabold text-[#24358a]">Activity Logs</p>
                </div>
                <span class="rounded-full bg-[#00386f] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white">Coming soon</span>
            </div>
            <p class="mt-3 text-sm leading-6 text-slate-700">A dedicated admin activity log center will be added here for audit trails, but it is not active yet.</p>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-4">
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