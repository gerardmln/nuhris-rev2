@extends('hr.layout')

@php
    $pageTitle = 'Employees';
    $pageHeading = 'Employees';
    $activeNav = 'employees';
@endphp

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-[#1f2b5d]">Employees</h2>
                <p class="text-sm text-slate-500">HR user CRUD module</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('dashboard') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Dashboard</a>
                <a href="{{ route('employees.create') }}" class="rounded-md bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">+ Add Employee</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('employees.index') }}" class="mb-4 rounded-xl border border-slate-300 bg-white p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search name, email, employee ID" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none md:col-span-2">

                <select name="department_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected($filters['department_id'] == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <select name="status" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="">All Status</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="on_leave" @selected($filters['status'] === 'on_leave')>On Leave</option>
                        <option value="resigned" @selected($filters['status'] === 'resigned')>Resigned</option>
                        <option value="terminated" @selected($filters['status'] === 'terminated')>Terminated</option>
                    </select>
                    <button type="submit" class="rounded-md bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Filter</button>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-300 bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($employees as $employee)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $employee->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $employee->employee_id }} | {{ $employee->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $employee->department?->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $employee->position }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', ucfirst($employee->status)) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('employees.show', $employee) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</a>
                                    <a href="{{ route('employees.edit', $employee) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No employee records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    </div>
@endsection
