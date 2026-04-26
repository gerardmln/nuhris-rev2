@extends('admin.layout')

@section('title', 'Cut-off & Schedules')
@section('page_title', 'Cut-off & Schedules')

@section('content')
    <div class="inline-flex rounded-lg bg-slate-300 p-1 text-sm font-semibold">
        <button id="cutoff-tab-dates" class="rounded-md bg-white px-4 py-2">Payroll Cut-off Dates</button>
        <button id="cutoff-tab-work" class="rounded-md px-4 py-2">Work Schedules</button>
    </div>

    <div id="cutoff-dates-view" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                <h3 class="text-lg font-bold">Calendar View</h3>
                <div class="mt-2 h-56 rounded border border-slate-300 bg-slate-50"></div>
            </article>

            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Calendar View</h3>
                    <button id="open-cutoff-modal" class="rounded-lg bg-[#242b34] px-4 py-2 text-xs font-semibold text-white">+ Add Period</button>
                </div>
                <table class="min-w-full text-left text-xs">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-2 py-2">Period</th>
                            <th class="px-2 py-2">Start Date</th>
                            <th class="px-2 py-2">End Date</th>
                            <th class="px-2 py-2">Pay Date</th>
                            <th class="px-2 py-2">Status</th>
                            <th class="px-2 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($periods as $period)
                            <tr>
                                <td class="px-2 py-2">{{ $period['period'] }}</td>
                                <td class="px-2 py-2">{{ $period['start_date'] }}</td>
                                <td class="px-2 py-2">{{ $period['end_date'] }}</td>
                                <td class="px-2 py-2">{{ $period['pay_date'] }}</td>
                                <td class="px-2 py-2">{{ $period['status'] }}</td>
                                <td class="px-2 py-2">...</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>
        </div>

        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-lg font-bold">Auto - Generate Settings</h3>
            <form method="POST" action="{{ route('admin.policy.cutoff.settings.update') }}">
                @csrf
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Cut-off Frequency</label>
                        <select name="frequency" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option @selected(($settings['frequency'] ?? '') === 'Semi-monthly')>Semi-monthly</option>
                            <option @selected(($settings['frequency'] ?? '') === 'Bi - Weekly')>Bi - Weekly</option>
                            <option @selected(($settings['frequency'] ?? '') === 'Weekly')>Weekly</option>
                            <option @selected(($settings['frequency'] ?? '') === 'Monthly')>Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Pay Delay (Days)</label>
                        <input name="pay_delay" type="number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ $settings['pay_delay'] ?? 5 }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Generate Ahead (Months)</label>
                        <input name="generate_ahead" type="number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ $settings['generate_ahead'] ?? 3 }}">
                    </div>
                </div>
                <div class="mt-3 text-right">
                    <button class="rounded-lg bg-[#242b34] px-4 py-2 text-xs font-semibold text-white">Save Settings</button>
                </div>
            </form>
        </article>
    </div>

    <div id="cutoff-work-view" class="hidden">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-lg font-bold">Work Schedules</h3>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach ($schedules as $sched)
                    <div class="rounded-xl border border-slate-300 bg-slate-50 p-3">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">{{ $sched['name'] }}</p>
                            <span class="text-xs text-slate-500">edit</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ $sched['time'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    <div id="cutoff-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h4 class="text-3xl font-bold">Add Cut-off Period</h4>
            <p class="text-sm text-slate-500">Define a new payroll cut-off period</p>
            <form method="POST" action="{{ route('admin.policy.cutoff.periods.store') }}">
                @csrf
                <div class="mt-3 space-y-2">
                    <input name="period" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="e.g., March 2026 - 1st Half" required>
                    <div class="grid grid-cols-2 gap-3">
                        <input name="start_date" type="date" class="rounded-xl border border-slate-300 px-3 py-2" required>
                        <input name="end_date" type="date" class="rounded-xl border border-slate-300 px-3 py-2" required>
                    </div>
                    <input name="pay_date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button id="close-cutoff-modal" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button class="rounded-lg bg-[#242b34] px-4 py-2 text-sm font-semibold text-white">+ Add Period</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const datesTab = document.getElementById('cutoff-tab-dates');
            const workTab = document.getElementById('cutoff-tab-work');
            const datesView = document.getElementById('cutoff-dates-view');
            const workView = document.getElementById('cutoff-work-view');

            const showDates = () => {
                datesView.classList.remove('hidden');
                workView.classList.add('hidden');
                datesTab.classList.add('bg-white');
                workTab.classList.remove('bg-white');
            };

            const showWork = () => {
                workView.classList.remove('hidden');
                datesView.classList.add('hidden');
                workTab.classList.add('bg-white');
                datesTab.classList.remove('bg-white');
            };

            datesTab.addEventListener('click', showDates);
            workTab.addEventListener('click', showWork);
            showDates();

            const modal = document.getElementById('cutoff-modal');
            document.getElementById('open-cutoff-modal').addEventListener('click', () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            document.getElementById('close-cutoff-modal').addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        })();
    </script>
@endpush