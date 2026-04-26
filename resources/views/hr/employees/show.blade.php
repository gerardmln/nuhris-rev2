@extends('hr.layout')

@php
    $pageTitle = 'Employee Details';
    $pageHeading = 'Employee Details';
    $activeNav = 'employees';
@endphp

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-3xl font-bold text-[#1f2b5d]">Employee Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('employees.edit', $employee) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                <a href="{{ route('employees.index') }}" class="rounded-md bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Back</a>
            </div>
        </div>

        <div class="rounded-xl border border-slate-300 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 text-sm text-slate-700 sm:grid-cols-2">
                <div><p class="text-xs text-slate-500">Employee ID</p><p class="font-semibold">{{ $employee->employee_id }}</p></div>
                <div><p class="text-xs text-slate-500">Name</p><p class="font-semibold">{{ $employee->full_name }}</p></div>
                <div><p class="text-xs text-slate-500">Email</p><p class="font-semibold">{{ $employee->email }}</p></div>
                <div><p class="text-xs text-slate-500">Phone</p><p class="font-semibold">{{ $employee->phone ?? 'N/A' }}</p></div>
                <div><p class="text-xs text-slate-500">Department</p><p class="font-semibold">{{ $employee->department?->name }}</p></div>
                <div><p class="text-xs text-slate-500">Position</p><p class="font-semibold">{{ $employee->position }}</p></div>
                <div><p class="text-xs text-slate-500">Employment Type</p><p class="font-semibold">{{ $employee->employment_type ?? 'N/A' }}</p></div>
                <div><p class="text-xs text-slate-500">Ranking</p><p class="font-semibold">{{ $employee->ranking ?? 'N/A' }}</p></div>
                <div><p class="text-xs text-slate-500">Status</p><p class="font-semibold">{{ str_replace('_', ' ', ucfirst($employee->status)) }}</p></div>
                <div><p class="text-xs text-slate-500">Hire Date</p><p class="font-semibold">{{ $employee->hire_date?->format('M d, Y') ?? 'N/A' }}</p></div>
                <div><p class="text-xs text-slate-500">Official Time In</p><p class="font-semibold">{{ $employee->official_time_in?->format('H:i') ?? 'N/A' }}</p></div>
                <div><p class="text-xs text-slate-500">Official Time Out</p><p class="font-semibold">{{ $employee->official_time_out?->format('H:i') ?? 'N/A' }}</p></div>
                <div><p class="text-xs text-slate-500">Resume Last Updated</p><p class="font-semibold">{{ $employee->resume_last_updated_at?->format('M d, Y') ?? 'N/A' }}</p></div>
            </div>
        </div>
    </div>
@endsection
