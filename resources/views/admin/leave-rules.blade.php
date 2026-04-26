@extends('admin.layout')

@section('title', 'Leave Computation Rules')
@section('page_title', 'Leave Computation Rules')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Leave Types</p><p class="text-4xl font-extrabold">{{ $stats['leave_types'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">With Rollover</p><p class="text-4xl font-extrabold">{{ $stats['with_rollover'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Employee Types</p><p class="text-4xl font-extrabold">{{ $stats['employee_types'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Max VL Credits</p><p class="text-4xl font-extrabold">{{ $stats['max_vl_credits'] }} days</p></article>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm xl:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xl font-bold">Leave Types</h3>
                <button id="open-leave-type-modal" class="rounded-lg bg-[#242b34] px-4 py-2 text-xs font-semibold text-white">+ Add Leave Type</button>
            </div>
            <table class="min-w-full text-left text-xs">
                <thead class="bg-slate-100"><tr><th class="px-2 py-2">Type</th><th class="px-2 py-2">Accrual</th><th class="px-2 py-2">Max Credits</th><th class="px-2 py-2">Rollover</th><th class="px-2 py-2">Applies To</th><th class="px-2 py-2">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($leaveTypes as $leaveType)
                        <tr>
                            <td class="px-2 py-2">{{ $leaveType['type'] }}</td>
                            <td class="px-2 py-2">{{ $leaveType['accrual'] }}</td>
                            <td class="px-2 py-2">{{ $leaveType['max'] }}</td>
                            <td class="px-2 py-2">{{ $leaveType['rollover'] }}</td>
                            <td class="px-2 py-2">{{ $leaveType['applies_to'] }}</td>
                            <td class="px-2 py-2">...</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </article>

        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-xl font-bold">Accrual Calculator</h3>
            <div class="mt-3 space-y-2 text-sm">
                <select class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option>Regular Employee</option>
                    <option>Probationary</option>
                    <option>Faculty (Full - Time)</option>
                    <option>Faculty (Part - Time)</option>
                    <option>Contractual</option>
                </select>
                <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value="12">
                <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value="5">
            </div>
        </article>
    </div>

    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <h3 class="text-xl font-bold">Leave Allocation by Employee Type</h3>
        <table class="mt-3 min-w-full text-left text-xs">
            <thead class="bg-slate-100"><tr><th class="px-2 py-2">Employee Type</th><th class="px-2 py-2">Vacation Leave</th><th class="px-2 py-2">Sick Leave</th><th class="px-2 py-2">Emergency Leave</th><th class="px-2 py-2">Total Credits</th></tr></thead>
            <tbody class="divide-y divide-slate-200">
                @foreach ($allocations as $allocation)
                    <tr>
                        <td class="px-2 py-2">{{ $allocation['employee_type'] }}</td>
                        <td class="px-2 py-2">{{ $allocation['vacation'] }}</td>
                        <td class="px-2 py-2">{{ $allocation['sick'] }}</td>
                        <td class="px-2 py-2">{{ $allocation['emergency'] }}</td>
                        <td class="px-2 py-2 font-semibold">{{ $allocation['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </article>

    <div id="leave-type-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
            <h4 class="text-3xl font-bold">Add Leave Type</h4>
            <p class="text-sm text-slate-500">Create a new leave type with accrual rules</p>
            <form method="POST" action="{{ route('admin.policy.leave.store') }}">
                @csrf
                <div class="mt-3 space-y-2">
                    <input name="type" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="e.g., Birthday Leave" required>
                    <div class="grid grid-cols-2 gap-3">
                        <input name="accrual" type="number" step="0.01" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="0">
                        <input name="max" type="number" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="15" required>
                    </div>
                    <select name="applies_to" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option>All Employees</option>
                        <option>Regular Employees</option>
                        <option>Faculty only</option>
                        <option>Female Employees</option>
                        <option>Male Employees</option>
                    </select>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button id="close-leave-type-modal" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button class="rounded-lg bg-[#242b34] px-4 py-2 text-sm font-semibold text-white">+ Add Type</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('leave-type-modal');
            document.getElementById('open-leave-type-modal').addEventListener('click', () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            document.getElementById('close-leave-type-modal').addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        })();
    </script>
@endpush