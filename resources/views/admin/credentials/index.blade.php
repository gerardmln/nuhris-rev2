@extends('admin.layout')

@section('title', 'Credential Management')

@section('content')
<div class="p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-slate-900">Credential Management</h1>
            <p class="text-slate-600 mt-2">Total: {{ $counts['total'] }} credentials</p>
        </div>
        <button onclick="if(confirm('Clear ALL credentials? This action cannot be undone.')) { document.getElementById('clear-form').submit(); }" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
            Clear All
        </button>
        <form id="clear-form" action="{{ route('admin.credentials.clear-all') }}" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex gap-4 mb-8 border-b border-slate-200">
        <a href="{{ route('admin.credentials.index', ['status' => 'pending']) }}" class="pb-4 px-4 {{ $statusFilter === 'pending' ? 'text-blue-600 border-b-2 border-blue-600 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
            Pending ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.credentials.index', ['status' => 'verified']) }}" class="pb-4 px-4 {{ $statusFilter === 'verified' ? 'text-blue-600 border-b-2 border-blue-600 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
            Verified ({{ $counts['verified'] }})
        </a>
        <a href="{{ route('admin.credentials.index', ['status' => 'rejected']) }}" class="pb-4 px-4 {{ $statusFilter === 'rejected' ? 'text-blue-600 border-b-2 border-blue-600 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
            Rejected ({{ $counts['rejected'] }})
        </a>
    </div>

    @if($credentials->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-slate-600">No credentials found.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Type</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Title</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Expires</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($credentials as $credential)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-slate-900">{{ $credential['employee_name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $credential['employee_email'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $credential['type_label'] }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $credential['title'] }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    @if($credential['status'] === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($credential['status'] === 'verified') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($credential['status']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $credential['expires_at'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm flex gap-2">
                                <a href="{{ route('admin.credentials.edit', $credential['id']) }}" class="text-blue-600 hover:text-blue-700 font-medium">Edit</a>
                                <button onclick="if(confirm('Delete this credential?')) { document.getElementById('delete-{{ $credential['id'] }}').submit(); }" class="text-red-600 hover:text-red-700 font-medium">Delete</button>
                                <form id="delete-{{ $credential['id'] }}" action="{{ route('admin.credentials.destroy', $credential['id']) }}" method="POST" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
