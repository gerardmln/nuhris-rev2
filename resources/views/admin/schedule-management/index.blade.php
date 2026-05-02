@extends('admin.layout')

@section('title', 'Schedule Management')

@section('content')
<div class="p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-slate-900">Schedule Management</h1>
            <p class="text-slate-600 mt-2">Total: {{ $counts['total'] }} submissions</p>
        </div>
        <button onclick="if(confirm('Reset ALL schedules? This action cannot be undone.')) { document.getElementById('reset-form').submit(); }" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
            Reset All Schedules
        </button>
        <form id="reset-form" action="{{ route('admin.schedules.reset-all') }}" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex gap-4 mb-8 border-b border-slate-200">
        <a href="{{ route('admin.schedules.index', ['status' => 'pending']) }}" class="pb-4 px-4 {{ $statusFilter === 'pending' ? 'text-blue-600 border-b-2 border-blue-600 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
            Pending ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.schedules.index', ['status' => 'approved']) }}" class="pb-4 px-4 {{ $statusFilter === 'approved' ? 'text-blue-600 border-b-2 border-blue-600 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
            Approved ({{ $counts['approved'] }})
        </a>
        <a href="{{ route('admin.schedules.index', ['status' => 'declined']) }}" class="pb-4 px-4 {{ $statusFilter === 'declined' ? 'text-blue-600 border-b-2 border-blue-600 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
            Declined ({{ $counts['declined'] }})
        </a>
    </div>

    @if($submissions->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-slate-600">No submissions found.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Submitted</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Reviewed</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($submissions as $submission)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $submission->employee?->full_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $submission->submitted_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    @if($submission->status === 'approved') bg-green-100 text-green-800
                                    @elseif($submission->status === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $submission->reviewed_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="if(confirm('Reset schedule for this employee?')) { document.getElementById('reset-{{ $submission->employee_id }}').submit(); }" class="text-orange-600 hover:text-orange-700 font-medium">Reset</button>
                                <form id="reset-{{ $submission->employee_id }}" action="{{ route('admin.schedules.employee.reset', $submission->employee_id) }}" method="POST" style="display:none;">
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
