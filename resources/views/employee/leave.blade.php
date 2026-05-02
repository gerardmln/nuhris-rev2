@extends('employee.layout')

@section('title', 'Leave Monitoring')
@section('page_title', 'Leave Monitoring')

@section('content')
    <p class="text-sm text-slate-600">View your leave balances and history. Leave data is managed by HR (read-only).</p>

    {{-- Leave Usage Summary --}}
    @if (isset($leaveUsage))
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3 mb-6">
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Vacation Used</p>
                <p class="mt-1 text-3xl font-extrabold text-emerald-600">{{ rtrim(rtrim(number_format($leaveUsage['vacation_used'], 1, '.', ''), '0'), '.') }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Sick Used</p>
                <p class="mt-1 text-3xl font-extrabold text-amber-600">{{ rtrim(rtrim(number_format($leaveUsage['sick_used'], 1, '.', ''), '0'), '.') }}</p>
            </article>
            <article class="rounded-2xl border border-purple-300 bg-purple-100 p-4 shadow-sm">
                <p class="text-sm font-medium text-purple-700">Emergency Used</p>
                <p class="mt-1 text-3xl font-extrabold text-purple-700">{{ rtrim(rtrim(number_format($leaveUsage['emergency_used'], 1, '.', ''), '0'), '.') }}</p>
            </article>
        </div>
    @endif

    {{-- Leave Balances (Deductible Only) --}}
    @if (count($leaveBalances) > 0)
        <div class="mb-6">
            <h3 class="mb-3 text-lg font-bold text-slate-900">Remaining Leave Balance</h3>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($leaveBalances as $balance)
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-sm text-slate-500">{{ $balance['type'] }}</p>
                        <p class="mt-1 text-3xl font-extrabold text-[#1f2b5d]">{{ $balance['remaining'] }}</p>
                        <p class="text-xs text-slate-500">Remaining days</p>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Detailed Leave Usage Breakdown --}}
    @if (isset($leaveUsageBreakdown))
        <div class="mb-6 rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-xl font-bold text-slate-900">Detailed Leave Usage</h3>

            {{-- Deductible Leaves (affect balance) --}}
            @if (count($leaveUsageBreakdown['deductible']) > 0)
                <div class="mb-6">
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-emerald-700">Leaves That Affect Balance</h4>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                        @foreach ($leaveUsageBreakdown['deductible'] as $usage)
                            @if ($usage['count'] > 0 || $usage['days_used'] > 0)
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                    <p class="text-sm font-medium text-emerald-900">{{ $usage['type'] }}</p>
                                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ rtrim(rtrim(number_format($usage['days_used'], 2, '.', ''), '0'), '.') }}</p>
                                    <p class="text-xs text-emerald-600">{{ $usage['count'] }} request(s)</p>
                                </div>
                            @else
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 opacity-50">
                                    <p class="text-sm font-medium text-emerald-900">{{ $usage['type'] }}</p>
                                    <p class="mt-1 text-2xl font-bold text-emerald-700">0</p>
                                    <p class="text-xs text-emerald-600">No usage</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Tracked-Only Leaves (do NOT affect balance) --}}
            @if (count($leaveUsageBreakdown['tracked_only']) > 0)
                <div>
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Tracked Leaves (Balance Not Affected)</h4>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($leaveUsageBreakdown['tracked_only'] as $usage)
                            @if ($usage['count'] > 0)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-sm font-medium text-slate-900">{{ $usage['type'] }}</p>
                                    <p class="mt-1 text-xl font-bold text-slate-700">{{ rtrim(rtrim(number_format($usage['days_used'], 2, '.', ''), '0'), '.') }}</p>
                                    <p class="text-xs text-slate-600">{{ $usage['count'] }} request(s)</p>
                                </div>
                            @else
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 opacity-50">
                                    <p class="text-sm font-medium text-slate-900">{{ $usage['type'] }}</p>
                                    <p class="mt-1 text-xl font-bold text-slate-700">0</p>
                                    <p class="text-xs text-slate-600">No usage</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-slate-500 italic">Note: These leaves are tracked for record-keeping but do not reduce your leave balance.</p>
                </div>
            @endif
        </div>
    @endif

    <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
        <h2 class="text-3xl font-bold text-slate-900">Leave History</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Start Date</th>
                        <th class="px-4 py-2">End Date</th>
                        <th class="px-4 py-2">Days Deducted</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Cut-off</th>
                        <th class="px-4 py-2">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaveHistory as $leave)
                        <tr>
                            <td class="px-4 py-2">{{ $leave['type'] }}</td>
                            <td class="px-4 py-2">{{ $leave['start'] }}</td>
                            <td class="px-4 py-2">{{ $leave['end'] }}</td>
                            <td class="px-4 py-2">{{ $leave['days'] }}</td>
                            <td class="px-4 py-2">{{ $leave['status'] }}</td>
                            <td class="px-4 py-2">{{ $leave['cutoff'] }}</td>
                            <td class="px-4 py-2">{{ $leave['reason'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-24 text-center text-2xl text-slate-400">No leave history found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection