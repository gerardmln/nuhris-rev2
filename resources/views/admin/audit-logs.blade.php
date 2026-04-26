@extends('admin.layout')

@section('title', 'Audit Logs')
@section('page_title', 'Audit Logs')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Total Logs Today</p><p class="text-4xl font-extrabold">{{ $stats['total'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Successful Actions</p><p class="text-4xl font-extrabold">{{ $stats['success'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Failed Actions</p><p class="text-4xl font-extrabold">{{ $stats['failed'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Active Users</p><p class="text-4xl font-extrabold">{{ $stats['active_users'] }}</p></article>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm md:w-96" placeholder="Search by user or description...">
        <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option>All Actions</option>
            <option>Create</option>
            <option>Update</option>
            <option>Delete</option>
            <option>Login</option>
            <option>Export</option>
            <option>Approve</option>
        </select>
        <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option>All Modules</option>
            <option>User Management</option>
            <option>Authentication</option>
            <option>Leave Management</option>
            <option>Compliance</option>
            <option>Reports</option>
            <option>System</option>
        </select>
        <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option>All Roles</option>
            <option>Admin</option>
            <option>HR Personnel</option>
            <option>Faculty</option>
            <option>System</option>
        </select>
    </div>

    <article class="overflow-x-auto rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <h3 class="text-2xl font-bold text-[#24358a]">Audit Log Entries</h3>
        <table class="mt-3 min-w-full text-left text-xs">
            <thead class="bg-slate-100"><tr><th class="px-2 py-2">Timestamp</th><th class="px-2 py-2">User</th><th class="px-2 py-2">Action</th><th class="px-2 py-2">Module</th><th class="px-2 py-2">Description</th><th class="px-2 py-2">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-2 py-2">{{ $log['timestamp'] }}</td>
                        <td class="px-2 py-2">{{ $log['user'] }}</td>
                        <td class="px-2 py-2">{{ $log['action'] }}</td>
                        <td class="px-2 py-2">{{ $log['module'] }}</td>
                        <td class="px-2 py-2">{{ $log['description'] }}</td>
                        <td class="px-2 py-2 {{ $log['status'] === 'Success' ? 'text-emerald-700' : 'text-red-700' }}">{{ $log['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-2 py-4 text-center text-slate-500">No logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>
@endsection