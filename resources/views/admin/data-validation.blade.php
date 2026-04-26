@extends('admin.layout')

@section('title', 'Data Validation')
@section('page_title', 'Data Validation')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Validation Rules</p><p class="text-4xl font-extrabold">{{ $stats['rules'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Active Rules</p><p class="text-4xl font-extrabold">{{ $stats['active_rules'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Required Field Sets</p><p class="text-4xl font-extrabold">{{ $stats['required_sets'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Errors Today</p><p class="text-4xl font-extrabold">{{ $stats['errors_today'] }}</p></article>
    </div>

    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-2xl font-bold text-[#24358a]">Validation Rules</h3>
            <button id="open-validation-rule-modal" class="rounded-lg bg-[#242b34] px-4 py-2 text-xs font-semibold text-white">+ Add Rule</button>
        </div>
        <div class="space-y-2">
            @foreach ($validationRules as $item)
                <div class="flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                    <p class="font-semibold">{{ $item }}</p>
                    <span class="text-xs text-slate-500">enabled</span>
                </div>
            @endforeach
        </div>
    </article>

    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <h3 class="text-2xl font-bold text-[#24358a]">Required Fields Configuration</h3>
        <div class="mt-3 space-y-2">
            @foreach ($requiredFields as $item)
                <div class="flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                    <p class="font-semibold">{{ $item }}</p>
                    <span class="text-xs text-slate-500">Configure</span>
                </div>
            @endforeach
        </div>
    </article>

    <div id="validation-rule-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h4 class="text-3xl font-bold">Add Cut-off Period</h4>
            <p class="text-sm text-slate-500">Define a new payroll cut-off period</p>
            <form method="POST" action="{{ route('admin.integration.validation.store') }}">
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
                    <button id="close-validation-rule-modal" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button class="rounded-lg bg-[#242b34] px-4 py-2 text-sm font-semibold text-white">+ Add Rule</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('validation-rule-modal');
            document.getElementById('open-validation-rule-modal').addEventListener('click', () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            document.getElementById('close-validation-rule-modal').addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        })();
    </script>
@endpush