@extends('admin.layout')

@section('title', 'Employees')
@section('page_title', 'Employees')

@section('content')
    <div class="p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-2xl font-bold">Employee Directory</h2>
        </div>

        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.employees.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name, email, or ID" class="w-1/3 rounded-md border px-3 py-2" />
                <select name="department_id" class="rounded-md border px-2 py-2">
                    <option value="">All Departments</option>
                    <option value="asp" @selected(($filters['department_id'] ?? '') === 'asp')>Admin Support Personnel</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                <button class="ml-auto rounded-md bg-blue-600 px-4 py-2 text-white">Filter</button>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto rounded-lg border bg-white p-2 shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $employee->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $employee->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $employee->department?->name }}</td>
                            <td class="px-4 py-3">{{ $employee->position }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.employees.edit', $employee) }}" class="rounded-md border px-3 py-1 text-sm">Edit</a>
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="rounded-md border px-3 py-1 text-sm">View Profile</a>
                                    <form method="POST" action="{{ route('admin.employees.resend-credentials', $employee) }}">
                                        @csrf
                                        <button class="rounded-md border px-3 py-1 text-sm text-blue-700">Resend Credentials</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}" onsubmit="return confirm('Permanently delete this employee? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md border px-3 py-1 text-sm text-red-600">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection
