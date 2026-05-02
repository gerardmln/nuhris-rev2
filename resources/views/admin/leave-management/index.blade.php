@extends('admin.layout')

@section('title', 'Leave Management')

@section('content')
<div class="p-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Leave Management</h1>
        <button onclick="if(confirm('Clear ALL leave data? This action cannot be undone.')) { document.getElementById('clear-form').submit(); }" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
            Clear All
        </button>
        <form id="clear-form" action="{{ route('admin.leave.clear-all') }}" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Leave Requests -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-slate-900 mb-4">Leave Requests</h2>
            
            @if($requests->isEmpty())
                <p class="text-slate-600">No leave requests found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Employee</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Type</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($requests->take(10) as $request)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 text-slate-900">{{ $request->employee?->full_name ?? 'N/A' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $request->leave_type }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold
                                            @if($request->status === 'approved') bg-green-100 text-green-800
                                            @elseif($request->status === 'pending') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-500 mt-4">Showing {{ min(10, $requests->count()) }} of {{ $requests->count() }} requests</p>
            @endif
        </div>

        <!-- Leave Balances -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-slate-900 mb-4">Leave Balances</h2>
            
            @if($balances->isEmpty())
                <p class="text-slate-600">No leave balances found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Employee</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Leave Type</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-900">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($balances->take(10) as $balance)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 text-slate-900">{{ $balance->employee?->full_name ?? 'N/A' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $balance->leave_type }}</td>
                                    <td class="px-3 py-2 font-semibold text-slate-900">{{ $balance->balance_remaining ?? '0' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-500 mt-4">Showing {{ min(10, $balances->count()) }} of {{ $balances->count() }} balances</p>
            @endif
        </div>
    </div>
</div>
@endsection
