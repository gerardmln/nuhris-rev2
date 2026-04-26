@extends('employee.layout')

@section('title', 'Notification')
@section('page_title', 'Notification')

@section('content')
    <p class="text-sm text-slate-600">Stay updated with credential reminders, HR announcements, and compliance alerts.</p>

    <div class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-[#c7c7c9] p-1 text-sm font-semibold text-slate-900">
        <button type="button" data-filter="all" class="notif-filter rounded-lg bg-[#d9d9db] px-4 py-2">All</button>
        <button type="button" data-filter="credentials" class="notif-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">Credentials</button>
        <button type="button" data-filter="ched" class="notif-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">CHED</button>
        <button type="button" data-filter="hr" class="notif-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">HR</button>
        <button type="button" data-filter="dtr" class="notif-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">DTR</button>
        <button type="button" data-filter="general" class="notif-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">General</button>
    </div>

    <div id="notif-list" class="space-y-4">
        @forelse ($notifications as $notification)
            @php
                $priority = $notification->announcement?->priority ?? 'medium';
            @endphp
            <article class="notif-card rounded-2xl border border-slate-300 bg-white px-6 py-5 shadow-sm" data-category="general">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <h3 class="text-2xl font-bold text-slate-900">{{ $notification->announcement?->title ?? 'Announcement' }}</h3>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">{{ $priority }}</span>
                </div>
                <p class="text-sm text-slate-700">{{ $notification->announcement?->content }}</p>
                <p class="mt-5 text-xs text-slate-400">{{ $notification->created_at->format('M d, Y \a\t h:i A') }}</p>
            </article>
        @empty
            <p class="py-24 text-center text-2xl text-slate-400">No notifications found</p>
        @endforelse
    </div>

    <p id="notif-empty" class="hidden py-24 text-center text-2xl text-slate-400">No notifications found</p>
@endsection

@push('scripts')
    <script>
        (() => {
            const buttons = document.querySelectorAll('.notif-filter');
            const cards = document.querySelectorAll('.notif-card');
            const empty = document.getElementById('notif-empty');

            const applyFilter = (filter) => {
                let visible = 0;

                buttons.forEach((button) => {
                    const active = button.dataset.filter === filter;
                    button.classList.toggle('bg-[#d9d9db]', active);
                });

                cards.forEach((card) => {
                    const show = filter === 'all' || card.dataset.category === filter;
                    card.classList.toggle('hidden', !show);
                    if (show) visible += 1;
                });

                empty.classList.toggle('hidden', visible !== 0);
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => applyFilter(button.dataset.filter));
            });

            applyFilter('all');
        })();
    </script>
@endpush