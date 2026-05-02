@extends('admin.layout')

@section('title', 'DTR Management')

@section('content')
<div class="p-8">
    <h1 class="text-4xl font-bold text-slate-900 mb-8">Daily Time Record (DTR) Management</h1>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form action="{{ route('admin.dtr.index') }}" method="GET" class="flex gap-4 flex-wrap">
        <form action="{{ route('admin.dtr.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">Employee</label>
                    <select name="employee_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected($employee?->id === $emp->id)>{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">From Date</label>
                    <input type="date" name="date_from" value="{{ $dateFrom?->format('Y-m-d') ?? '' }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-900 mb-2">To Date</label>
                    <input type="date" name="date_to" value="{{ $dateTo?->format('Y-m-d') ?? '' }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div class="flex flex-col justify-end gap-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                        🔍 Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if($records->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-slate-600">No records found.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Time In</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Time Out</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($records as $record)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $record->employee?->full_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $record->record_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $record->time_in ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $record->time_out ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    @if($record->status === 'present') bg-green-100 text-green-800
                                    @elseif($record->status === 'absent') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('admin.dtr.edit', $record->id) }}" class="text-blue-600 hover:text-blue-700 font-medium">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
