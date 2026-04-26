@extends('admin.layout')

@section('title', 'Compliance Rules')
@section('page_title', 'Compliance Rules')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">CHED Compliance</p><p class="text-4xl font-extrabold">{{ $stats['ched_compliance'] }}%</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">PRC Valid</p><p class="text-4xl font-extrabold">{{ $stats['prc_valid'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Expiring Soon</p><p class="text-4xl font-extrabold">{{ $stats['expiring_soon'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Pending Documents</p><p class="text-4xl font-extrabold">{{ $stats['pending_documents'] }}</p></article>
    </div>

    <div class="inline-flex rounded-lg bg-slate-300 p-1 text-xs font-semibold">
        <button class="compliance-tab rounded-md bg-white px-4 py-2" data-target="ched">CHED Compliance</button>
        <button class="compliance-tab rounded-md px-4 py-2" data-target="prc">PRC Validation</button>
        <button class="compliance-tab rounded-md px-4 py-2" data-target="alert">Alert Settings</button>
    </div>

    <article id="compliance-ched" class="compliance-panel rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <h3 class="text-2xl font-bold">CHED Compliance Checklist</h3>
        <div class="mt-3 space-y-2">
            @foreach ($chedItems as $item)
                <div class="flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                    <p class="font-semibold">{{ $item }}</p>
                    <span class="text-xs text-slate-500">compliant</span>
                </div>
            @endforeach
        </div>
    </article>

    <article id="compliance-prc" class="compliance-panel hidden rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <h3 class="text-2xl font-bold">PRC License Validation Rules</h3>
        <div class="mt-3 space-y-2">
            @foreach ($prcRules as $item)
                <div class="flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                    <p class="font-semibold">{{ $item }}</p>
                    <span class="text-xs text-slate-500">edit</span>
                </div>
            @endforeach
        </div>
    </article>

    <article id="compliance-alert" class="compliance-panel hidden rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-2xl font-bold">Validation Rules</h3>
            <button id="open-rule-modal" class="rounded-lg bg-[#242b34] px-4 py-2 text-xs font-semibold text-white">+ Add Rule</button>
        </div>
        <div class="space-y-2">
            @foreach ($alertRules as $rule)
                <div class="flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                    <p class="font-semibold">{{ $rule }}</p>
                    <span class="text-xs text-slate-500">on</span>
                </div>
            @endforeach
        </div>
    </article>

    <div id="rule-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h4 class="text-3xl font-bold">Add Cut-off Period</h4>
            <p class="text-sm text-slate-500">Define a new payroll cut-off period</p>
            <form method="POST" action="{{ route('admin.policy.compliance.store') }}">
                @csrf
                <div class="mt-3 space-y-2">
                    <input name="field_label" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="e.g., Email Format" required>
                    <input name="field_name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="e.g., email">
                    <select name="rule_type" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option>Select Type</option>
                        <option>Regular Expression</option>
                        <option>Number Range</option>
                        <option>Date Range</option>
                        <option>Length Constraint</option>
                        <option>Allowed Values</option>
                    </select>
                    <input name="rule_value" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="e.g., [2392fnjc]2o]@34 +@3">
                    <textarea name="message" class="w-full rounded-xl border border-slate-300 px-3 py-2" rows="3" placeholder="Message to display when validation fails"></textarea>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button id="close-rule-modal" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button class="rounded-lg bg-[#242b34] px-4 py-2 text-sm font-semibold text-white">+ Add Rule</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const tabs = document.querySelectorAll('.compliance-tab');
            const panels = document.querySelectorAll('.compliance-panel');

            const activatePanel = (target) => {
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.id !== `compliance-${target}`));
                tabs.forEach((tab) => tab.classList.toggle('bg-white', tab.dataset.target === target));
            };

            tabs.forEach((tab) => tab.addEventListener('click', () => activatePanel(tab.dataset.target)));
            activatePanel('ched');

            const modal = document.getElementById('rule-modal');
            document.getElementById('open-rule-modal').addEventListener('click', () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            document.getElementById('close-rule-modal').addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        })();
    </script>
@endpush