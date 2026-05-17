@extends('admin.layout')

@section('title', 'Role Management')

@section('content')
<div class="p-8">
    <h1 class="text-4xl font-bold text-slate-900 mb-8">Role Management</h1>

    <!-- Role Distribution -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <p class="text-slate-600 text-sm font-medium mb-2">Admins</p>
            <p class="text-4xl font-bold text-slate-900">{{ $roleDistribution['Admin'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <p class="text-slate-600 text-sm font-medium mb-2">HR Personnel</p>
            <p class="text-4xl font-bold text-slate-900">{{ $roleDistribution['HR'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <p class="text-slate-600 text-sm font-medium mb-2">Employees</p>
            <p class="text-4xl font-bold text-slate-900">{{ $roleDistribution['Employee'] }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-slate-300 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.roles.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-8">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name or email" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none md:col-span-3">
            <select name="role" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                <option value="all" @selected(($filters['role'] ?? 'all') === 'all')>All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role['value'] }}" @selected((string) ($filters['role'] ?? '') === (string) $role['value'])>{{ $role['label'] }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <select name="department_id" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none md:col-span-2">
                <option value="all" @selected(($filters['department_id'] ?? 'all') === 'all')>All Departments</option>
                <option value="asp" @selected(($filters['department_id'] ?? '') === 'asp')>Admin Support Personnel</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Users List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Current Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Department</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Change Role</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($users as $user)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $user['name'] }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $user['email'] }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                @if($user['user_type'] === 1) bg-red-100 text-red-800
                                @elseif($user['user_type'] === 2) bg-blue-100 text-blue-800
                                @else bg-slate-100 text-slate-800
                                @endif">
                                {{ $user['role'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $user['department'] }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $user['status'] }}</td>
                            <td class="px-6 py-4 text-sm">
                                <form action="{{ route('admin.roles.update', $user['id']) }}" method="POST" class="flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="user_type" class="px-3 py-1 border border-slate-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @foreach($roles as $role)
                                            <option value="{{ $role['value'] }}" @selected($user['user_type'] === $role['value'])>{{ $role['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded text-sm font-medium">Save</button>
                                </form>
                            </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-900"><strong>Note:</strong> At least one Admin user must always exist. The system will prevent you from removing the last Admin.</p>
    </div>
</div>
@endsection
