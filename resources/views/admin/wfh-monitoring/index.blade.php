@extends('admin.layout')

@section('title', 'WFH Monitoring')

@section('content')
<div class="p-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Work From Home (WFH) Monitoring</h1>
        <button onclick="if(confirm('Clear ALL WFH records? This action cannot be undone.')) { document.getElementById('clear-form').submit(); }" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
            Clear All
        </button>
        <form id="clear-form" action="{{ route('admin.wfh-monitoring.clear-all') }}" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>

    @if($submissions->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-slate-600">No WFH submissions found.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Employee</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">WFH Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Submitted</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Approved</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($submissions as $submission)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $submission->employee?->full_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $submission->wfh_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    @if($submission->status === 'approved') bg-green-100 text-green-800
                                    @elseif($submission->status === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $submission->created_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $submission->reviewed_at?->format('M d, Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
