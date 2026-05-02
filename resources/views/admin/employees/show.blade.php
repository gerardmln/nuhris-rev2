@extends('admin.layout')

@section('title', 'Employee')
@section('page_title', 'Employee Details')

@section('content')
    <div class="p-6">
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <h3 class="text-xl font-bold">{{ $employee->full_name }}</h3>
            <p class="text-sm text-slate-600">{{ $employee->email }}</p>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <strong>Employee ID</strong>
                    <div class="text-sm">{{ $employee->employee_id }}</div>
                </div>
                <div>
                    <strong>Department</strong>
                    <div class="text-sm">{{ $employee->department?->name }}</div>
                </div>
                <div>
                    <strong>Position</strong>
                    <div class="text-sm">{{ $employee->position }}</div>
                </div>
                <div>
                    <strong>Status</strong>
                    <div class="text-sm">{{ $employee->status }}</div>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <a href="{{ route('admin.employees.edit', $employee) }}" class="rounded-md bg-blue-600 px-4 py-2 text-white">Edit</a>
                <form method="POST" action="{{ route('admin.employees.resend-credentials', $employee) }}">
                    @csrf
                    <button class="rounded-md border px-4 py-2 text-blue-700">Resend Credentials</button>
                </form>
            </div>
        </div>
    </div>
@endsection
