@extends('admin.layout')

@section('title', 'Activity Logs')
@section('page_title', 'Activity Logs')

@section('content')
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1.5fr_1fr]">
        <article class="rounded-xl border border-slate-300 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin module</p>
                    <h1 class="mt-1 text-3xl font-bold text-[#24358a]">Activity Logs</h1>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">Coming soon</span>
            </div>

            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                {{ $message ?? 'Activity Logs module is reserved for future development.' }}
            </p>

            <div class="mt-6 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-700">Planned use</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    This section will eventually track administrative actions, account changes, and other audit events across the HRIS.
                </p>
            </div>
        </article>

        <article class="rounded-xl border border-[#ffdc00] bg-[#fff9d6] p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#7a6500]">Status</p>
            <h2 class="mt-1 text-2xl font-bold text-[#24358a]">Coming soon</h2>
            <p class="mt-3 text-sm leading-6 text-slate-700">
                The module is wired into the admin menu, but the activity feed itself is not active yet.
            </p>
        </article>
    </div>
@endsection
