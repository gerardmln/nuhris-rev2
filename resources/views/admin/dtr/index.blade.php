@extends('admin.layout')

@section('title', 'DTR Management')

@section('content')
@extends('admin.layout')

@php
    $pageTitle = 'DTR Management';
    $pageHeading = 'DTR Management';
@endphp

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">View biometric attendance records and manage DTR</p>
        </div>
    </div>

    <article class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm mt-4">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
            <form method="GET" action="{{ route('admin.dtr.index') }}" class="md:col-span-3">
                <div class="flex flex-col gap-2 lg:flex-row">
                    <input type="text" name="search" value="{{ request('search') ?? '' }}" placeholder="Search by name, email, ID, or department..."
                           class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">

                    <select name="period" onchange="
                        var parts = this.value.split('-');
                        var first = new Date(parts[1], parts[0]-1, 1);
                        var last = new Date(parts[1], parts[0], 0);
                        this.form.querySelector('[name=date_from]').value = first.toISOString().slice(0,10);
                        this.form.querySelector('[name=date_to]').value = last.toISOString().slice(0,10);
                        this.form.submit();
                    " class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm focus:border-blue-400 focus:outline-none lg:w-56">
                        @foreach(collect(range(0,11))->map(fn($offset) => now()->startOfMonth()->subMonths($offset))->values() as $d)
                            <option value="{{ $d->month }}-{{ $d->year }}" @selected($d->month == $dateFrom->month && $d->year == $dateFrom->year)>{{ $d->format('F Y') }}</option>
                        @endforeach
                    </select>

                    <select name="employee_class" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm focus:border-blue-400 focus:outline-none lg:min-w-[14rem]">
                        <option value="all">All Employee Types</option>
                        <option value="regular" @selected(request('employee_class') === 'regular')>Regular Employees</option>
                        <option value="irregular" @selected(request('employee_class') === 'irregular')>Non-Regular Employee</option>
                    </select>

                    <input type="hidden" name="date_from" value="{{ $dateFrom?->format('Y-m-d') }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo?->format('Y-m-d') }}">
                </div>
            </form>
        </div>
    </article>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 mt-4">
        @forelse($employeeCards as $card)
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm transition hover:shadow-md">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#00386f] text-sm font-semibold text-white">{{ $card['initials'] }}</span>
                        <div>
                            <p class="text-xl font-bold text-[#1f2b5d]">{{ $card['name'] }}</p>
                            <p class="text-sm text-slate-500">{{ $card['department'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-emerald-100 p-3 text-center">
                        <p class="text-4xl font-extrabold text-emerald-700">{{ $card['present'] }}</p>
                        <p class="text-xs font-semibold text-emerald-700">Present</p>
                    </div>
                    <div class="rounded-lg bg-amber-100 p-3 text-center">
                        <p class="text-4xl font-extrabold text-amber-700">{{ $card['tardiness'] }}</p>
                        <p class="text-xs font-semibold text-amber-700">Tardiness(min)</p>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold text-red-800">Absences: {{ $card['absences'] }}</span>
                </div>
                @unless($card['has_data'])
                    <p class="mt-3 rounded-md border border-dashed border-slate-300 bg-slate-50 px-2 py-1 text-center text-[11px] text-slate-500">No biometric data yet for this period.</p>
                @endunless
                <p class="mt-3 text-xs text-slate-500">Schedule: {{ $card['schedule_summary'] }}</p>
                <a href="{{ route('admin.dtr.index', ['employee_id' => $card['id'], 'date_from' => $dateFrom?->format('Y-m-d'), 'date_to' => $dateTo?->format('Y-m-d')]) }}" class="mt-3 block w-full rounded-md bg-[#00386f] px-3 py-2 text-center text-sm font-semibold text-white hover:bg-[#002f5d] transition">View DTR</a>
            </article>
        @empty
            <div class="col-span-full rounded-xl border border-slate-200 bg-white p-8 text-center">
                <p class="text-lg font-semibold text-slate-500">No employees found</p>
                <p class="text-sm text-slate-400">Try adjusting your search or filter criteria.</p>
            </div>
        @endforelse
    </div>
@endsection
