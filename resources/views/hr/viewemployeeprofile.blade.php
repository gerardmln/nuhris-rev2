@extends('hr.layout')

@php
    $pageTitle = 'Employee Profile';
    $pageHeading = 'Employee Profile';
    $activeNav = 'employees';
@endphp

@section('content')
    <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900">
        <span>&larr;</span>
        Back to Employees
    </a>

    <section class="rounded-xl border border-slate-300 bg-white p-6 shadow-sm">
        @if ($employee)
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-[#1f2b5d]">{{ $employee->full_name }}</h2>
                    <p class="text-sm text-slate-500">{{ $employee->position ?? 'No position set' }}</p>
                </div>
                <span class="rounded-md bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ ucfirst($employee->status) }}</span>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="font-semibold">Email</dt><dd>{{ $employee->email }}</dd></div>
                <div><dt class="font-semibold">Phone</dt><dd>{{ $employee->phone ?? 'N/A' }}</dd></div>
                <div><dt class="font-semibold">Department</dt><dd>{{ $employee->department->name ?? 'Unassigned' }}</dd></div>
                <div><dt class="font-semibold">Hired</dt><dd>{{ optional($employee->hire_date)->format('M d, Y') ?? 'N/A' }}</dd></div>
                <div><dt class="font-semibold">Official Time</dt><dd>{{ optional($employee->official_time_in)->format('H:i') ?? '08:30' }} - {{ optional($employee->official_time_out)->format('H:i') ?? '17:30' }}</dd></div>
                <div><dt class="font-semibold">Employee ID</dt><dd>{{ $employee->employee_id }}</dd></div>
            </dl>
        @else
            <p class="text-slate-500">No employee record found.</p>
        @endif
    </section>
@endsection
