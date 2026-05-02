@extends('admin.layout')

@section('title', 'Edit Employee')
@section('page_title', 'Edit Employee')

@section('content')
    <div class="p-6">
        <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">First name</label>
                    <input name="first_name" value="{{ old('first_name', $employee->first_name) }}" class="mt-1 w-full rounded-md border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium">Last name</label>
                    <input name="last_name" value="{{ old('last_name', $employee->last_name) }}" class="mt-1 w-full rounded-md border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium">Email</label>
                    <input name="email" value="{{ old('email', $employee->email) }}" class="mt-1 w-full rounded-md border px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium">Department</label>
                    <select name="department_id" class="mt-1 w-full rounded-md border px-3 py-2">
                        <option value="">-- Select Department --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id', $employee->department_id) == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button class="rounded-md bg-blue-600 px-4 py-2 text-white">Save</button>
                <a href="{{ route('admin.employees.index') }}" class="rounded-md border px-4 py-2">Cancel</a>
            </div>
        </form>
    </div>
@endsection
