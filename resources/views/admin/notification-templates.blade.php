@extends('admin.layout')

@section('title', 'Notification Templates')
@section('page_title', 'Notification Templates')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Email Templates</p><p class="text-4xl font-extrabold">{{ $stats['email'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">SMS Templates</p><p class="text-4xl font-extrabold">{{ $stats['sms'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">In-App Templates</p><p class="text-4xl font-extrabold">{{ $stats['inapp'] }}</p></article>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-lg bg-slate-300 p-1 text-xs font-semibold">
            <button class="template-tab rounded-md bg-white px-4 py-2" data-template-tab="email">Email</button>
            <button class="template-tab rounded-md px-4 py-2" data-template-tab="sms">SMS</button>
            <button class="template-tab rounded-md px-4 py-2" data-template-tab="inapp">In-App</button>
        </div>
        <button id="open-template-modal" class="rounded-lg bg-[#242b34] px-4 py-2 text-xs font-semibold text-white">+ Add Template</button>
    </div>

    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <div class="space-y-2">
            <div class="template-list" data-template-panel="email">
                @foreach ($templates['email'] as $item)
                    <div class="mb-2 flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                        <p class="font-semibold">{{ $item }}</p>
                        <span class="text-xs text-slate-500">edit</span>
                    </div>
                @endforeach
            </div>

            <div class="template-list hidden" data-template-panel="sms">
                @foreach ($templates['sms'] as $item)
                    <div class="mb-2 flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                        <p class="font-semibold">{{ $item }}</p>
                        <span class="text-xs text-slate-500">edit</span>
                    </div>
                @endforeach
            </div>

            <div class="template-list hidden" data-template-panel="inapp">
                @foreach ($templates['inapp'] as $item)
                    <div class="mb-2 flex items-center justify-between rounded-lg bg-[#cfe1f5] px-4 py-3">
                        <p class="font-semibold">{{ $item }}</p>
                        <span class="text-xs text-slate-500">edit</span>
                    </div>
                @endforeach

                <button id="open-template-modal-inline" type="button" class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-slate-400 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <span class="text-base">+</span>
                    Add Template
                </button>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h4 class="text-lg font-bold">Available Template Variables</h4>
            <p class="text-xs text-slate-500">Use these variables in your templates to personalize content</p>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                @foreach ($tokens as $token)
                    <span class="rounded bg-slate-200 px-2 py-1">{{ $token }}</span>
                @endforeach
            </div>
        </div>
    </article>

    <div id="template-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4">
        <div class="w-full max-w-xl rounded-2xl border border-slate-300 bg-white p-6 shadow-xl">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h4 class="text-3xl font-bold text-[#24358a]">Add Template</h4>
                    <p class="text-sm text-slate-500">Create a new notification template</p>
                </div>
                <button id="close-template-modal" type="button" class="text-xl text-slate-600">x</button>
            </div>

            <form class="space-y-3" method="POST" action="{{ route('admin.policy.templates.store') }}">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold">Template Type</label>
                    <select name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                        <option value="inapp">In-App</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold">Template Name</label>
                    <input name="name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g., Compliance Reminder" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold">Trigger</label>
                    <input name="trigger" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g., PRC license expiring (30 days)">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold">Message</label>
                    <textarea name="message" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" rows="4" placeholder="Type the template message..."></textarea>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button id="cancel-template-modal" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button id="save-template-modal" type="submit" class="rounded-lg bg-[#083b72] px-4 py-2 text-sm font-semibold text-white">Add Template</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const tabs = document.querySelectorAll('.template-tab');
            const lists = document.querySelectorAll('.template-list');

            const activateTab = (tab) => {
                tabs.forEach((button) => button.classList.toggle('bg-white', button.dataset.templateTab === tab));
                lists.forEach((list) => list.classList.toggle('hidden', list.dataset.templatePanel !== tab));
            };

            tabs.forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.templateTab)));
            activateTab('email');
        })();

        (() => {
            const modal = document.getElementById('template-modal');
            const openMain = document.getElementById('open-template-modal');
            const openInline = document.getElementById('open-template-modal-inline');
            const closeBtn = document.getElementById('close-template-modal');
            const cancelBtn = document.getElementById('cancel-template-modal');
            const saveBtn = document.getElementById('save-template-modal');

            if (!modal) return;

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            openMain?.addEventListener('click', openModal);
            openInline?.addEventListener('click', openModal);
            closeBtn?.addEventListener('click', closeModal);
            cancelBtn?.addEventListener('click', closeModal);
            saveBtn?.addEventListener('click', () => {
                // Allow form submit and route handling.
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        })();
    </script>
@endpush